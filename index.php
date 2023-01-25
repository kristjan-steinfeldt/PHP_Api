<?php
 
include "Database.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE");

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriData = explode( '/', $uri );
$requestBodyText = file_get_contents('php://input');
$requestBody = json_decode($requestBodyText);
$conn = mysqli_connect($servername, $username, $password,$db) or die("Connect failed: %s\n". $conn -> error);
function append_to_file($line_to_append) {
  $date = date('Y/m/d H:i:s');
  $file_path = dirname(__FILE__) . '/logs.txt';
  $file = fopen($file_path, "a");
  fwrite($file, $line_to_append. ", '$date' \n");
  fclose($file);
}
function escape($data) {
  return $data = str_replace(',', '\,', $data);
}

function check_session($session) {
  global $conn;
  $sql ="SELECT * FROM sessions WHERE id='be16f2a0';";
  $result = mysqli_query($conn, $sql);
  $test = mysqli_fetch_assoc($result);
  if ($test['id']==$session) {
      return true;
  } else {
      return false;
  }

}



switch ($method) {
  case 'PUT':

      $session = $requestBody-> session;
      $name =$requestBody->name;
      $id=$requestBody->id;

      if (check_session($session)){
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
      }
      else{
        http_response_code(401);
        echo json_encode(
          array('message' => 'Invalid session')
      );
      break;
    }
    case 'POST':
      if ($uriData[2]== 'sessions') {
        $username= $requestBody->username;
        $password=$requestBody->password;
         $sql="SELECT id FROM users WHERE usename = '$username' AND password = '$password';";
          $users = mysqli_query($conn, $sql);
      $user = mysqli_fetch_assoc($users);
        if (count($user) === 0) {
            http_response_code(401);
            echo json_encode(
                array('message' => 'Invalid username or password')
            );
            exit();
        }

        // Create session
   
      $userid =  $user['id'][0];
      $bytes=random_bytes(4);
      $sessionId = bin2hex($bytes);
      $sql = "INSERT INTO sessions (id,userid) VALUES ('$sessionId','$userid');";
      $insert= mysqli_query($conn, $sql);
      $sql2 = "SELECT * FROM sessions WHERE id='$sessionId';";

         $session= mysqli_query($conn, $sql2);
         $thisSession = mysqli_fetch_assoc($session);
        // Return session
        echo json_encode(
            array('sessionId' => $thisSession['id'],'user' => $userid)
        );
      } else {

      $name = $requestBody->name;
      $userid = $requestBody->userid;
      $session = $requestBody->session;
      if (check_session($session)) {
        $sql = "INSERT INTO playlists (name,description,public,userid) VALUES ('$name', 'description','true','$userid');";
        echo "Created $name";
        $result = mysqli_query($conn, $sql);

        append_to_file("POST, $userid, " . escape($name));
        break;
      } else {
        http_response_code(401);
        echo json_encode(
          array('message' => 'Invalid session')
        );
      }
    }
      case 'GET':
      $userid=$uriData[2];
      $sql = "SELECT id ,name FROM playlists WHERE userid='$userid'";
        $result = mysqli_query($conn, $sql);

        $userArr = array();
        while ($row = mysqli_fetch_assoc($result))
        {
            $userArr[] = $row;
        }
        $data =array("items" => $userArr);
    
        echo json_encode($data,JSON_UNESCAPED_SLASHES);
      break;
    case strtoupper('delete'):
      $session = $requestBody->session;
    if (check_session($session)) {
      $id = $uriData[2];
      $sql = "DELETE FROM playlists WHERE id = $id;";
      $result = mysqli_query($conn, $sql);
      append_to_file("DELETE,$id");
    }else{
      http_response_code(401);
      echo json_encode(
        array('message' => 'Invalid session')
      );
    }

        break;
    default:
    echo json_encode(
        array('message' => 'method unknown')
    );
      break;
  }

?>
