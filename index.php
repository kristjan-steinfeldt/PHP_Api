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

function append_to_file($line_to_append) {
  $date = date('Y/m/d H:i:s');
  $file_path = dirname(__FILE__) . '/logs.txt';
  $file = fopen($file_path, "a");
  fwrite($file, $line_to_append . ",'$date' \n");
  fclose($file);
}
switch ($method) {
    case 'PUT':

      $name =$requestBody->name;
      $id=$requestBody->id;
      // $name="updatedname";
      // $id="1";
      $conn = mysqli_connect($servername, $username, $password, $db) or die("Connect failed: %s\n". $conn -> error);
      
      $stmt = mysqli_prepare($conn, "UPDATE playlists SET name = ? WHERE id = ?");
      mysqli_stmt_bind_param($stmt, "si", $name, $id);
      
      if (mysqli_stmt_execute($stmt)) {
        echo "Playlist $id, $name updated successfully.";
        append_to_file("PUT, $id, $name");
      } else {
        echo "Error updating playlist: " . mysqli_error($conn);
      }

      mysqli_stmt_close($stmt);
      mysqli_close($conn);
      break;
    case 'POST':

      $name =$requestBody->name;
      $userid =$requestBody->userid;
      $conn = mysqli_connect($servername, $username, $password,$db) or die("Connect failed: %s\n". $conn -> error);
      $sql = "INSERT INTO playlists (name,description,public,userid) VALUES ('$name', 'description','true','$userid');";
        $result = mysqli_query($conn, $sql);
     
      echo "Created $name";
      append_to_file("POST, $name, $userid");
      break;
    case 'GET':
      $userid=$uriData[2];
      $conn = mysqli_connect($servername, $username, $password,$db) or die("Connect failed: %s\n". $conn -> error);
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
          $id=$uriData[2];
          $sql = "DELETE FROM playlists WHERE id = $id;";
          $conn = mysqli_connect($servername, $username, $password,$db) or die("Connect failed: ". $conn -> error);
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
