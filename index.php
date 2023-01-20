<?php

include "Database.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE");

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$project_directory = dirname($_SERVER['SCRIPT_NAME']);
$uri = substr($path, strlen($project_directory));$uriData = explode('/', $uri);

$requestBodyText = file_get_contents('php://input');
$requestBody = json_decode($requestBodyText);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function append_to_file($line_to_append)
{
    $date = date('Y/m/d H:i:s');
    $file_path = dirname(__FILE__) . '/logs.txt';
    $file = fopen($file_path, "a");
    fwrite($file, $line_to_append . ",'$date' \n");
    fclose($file);
}

function q($sql)
{
    global $conn;
    $query = mysqli_query($conn, $sql);
    // Handle error
    if (!$query) {
        $error = mysqli_error($conn);

        // Return error 500
        http_response_code(500);
        echo json_encode(array(
            "error" => $error
        ));

        // Stop execution
        exit();

    }
    $result = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $result[] = $row;
    }
    // Return the result or empty array if result is empty
    return $result;
}

$conn = mysqli_connect($servername, $username, $password, $db) or die("Connect failed: %s\n" . $conn->error);

function validateParameters($requestBody, $parameters)
{
    foreach ($parameters as $parameter) {
        if (!isset($requestBody->$parameter)) {
            // Return 400 Bad Request
            http_response_code(400);
            // Output error message in JSON
            echo json_encode(
                array('message' => "Parameter $parameter is missing")
            );
            // Stop execution
            exit();
        }
    }
}

// Function to validate the session
function validateSession($sessionId)
{
    // Get the session from the database
    $sessions = q("SELECT * FROM sessions WHERE id = '$sessionId'");
    // Check if the session exists
    if (count($sessions) == 0) {
        // Return 401 Unauthorized
        http_response_code(401);
        // Output error message in JSON
        echo json_encode(
            array('message' => 'Invalid session')
        );
        // Stop execution
        exit();
    }
    // Return the session
    return $sessions[0];
}


function escape($data)
{
    return str_replace(',', '\,', $data);
}

switch ($method) {
    case 'PUT':

        $name = $requestBody->name;
        $id = $requestBody->id;
        // $name="updatedname";
        // $id="1";

        $stmt = mysqli_prepare($conn, "UPDATE playlists SET name = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $name, $id);

        if (mysqli_stmt_execute($stmt)) {
            echo "Playlist $id, $name updated successfully.";
            append_to_file("PUT, $id, ".escape($name));
        } else {
            echo "Error updating playlist: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        break;
    case 'POST':
        switch ($uri) {

            case '/sessions':

                // Validate input parameters
                validateParameters($requestBody, ['username', 'password']);

                $users = q("SELECT id FROM users WHERE username = '$requestBody->username' AND password = '$requestBody->password'");

                if (count($users) == 0) {
                    http_response_code(401);
                    echo json_encode(
                        array('message' => 'Invalid username or password')
                    );
                    exit();
                }

                // Create session
                $userid = $users[0]['id'];

                $session = q("INSERT INTO sessions (id,userid) VALUES (" . bin2hex(random_bytes(16)) . ", $userid)");

                // Return session
                echo json_encode(
                    array('sessionId' => $session['id'])
                );

                break;

            case '/playlists':

                // Get the bearer token value from Authorization header
                $bearerToken = explode(' ', $_SERVER['HTTP_AUTHORIZATION'])[1];

                // Validate session
                $session = validateSession($bearerToken);

                $name = $requestBody->name;
                $userid = $requestBody->userid;
                $sql = "INSERT INTO playlists (name,description,public,userid) VALUES ('$name', 'description','true','$userid');";
                $result = mysqli_query($conn, $sql);

                echo "Created $name";
                append_to_file("POST, ".escape($name).", $userid");
                break;
            case 'GET':
                $userid = $uriData[2];

                $sql = "SELECT id ,name FROM playlists WHERE userid='$userid'";
                $result = mysqli_query($conn, $sql);

                $userArr = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $userArr[] = $row;
                }
                $data = array("items" => $userArr);

                echo json_encode($data, JSON_UNESCAPED_SLASHES);

                break;
        }

    case strtoupper('delete'):
        $id = $uriData[2];
        $sql = "DELETE FROM playlists WHERE id = $id;";

        $result = mysqli_query($conn, $sql);
        append_to_file("DELETE,$id");

        break;
    default:
        echo json_encode(
            array('message' => 'method unknown')
        );
        break;
}

?>