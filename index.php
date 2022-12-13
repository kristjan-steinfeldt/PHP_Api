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

switch ($method) {
    case 'PUT':

      $name =$requestBody->PlaylistName;
      $id=$requestBody->PlaylistId;
      // $name="updatedname";
      // $id="1";
      $conn = mysqli_connect($servername, $username, $password, $db) or die("Connect failed: %s\n". $conn -> error);
      
      $stmt = mysqli_prepare($conn, "UPDATE playlists SET name = ? WHERE id = ?");
      mysqli_stmt_bind_param($stmt, "si", $name, $id);
      
      if (mysqli_stmt_execute($stmt)) {
        echo "Playlist $id, $name updated successfully.";
      } else {
        echo "Error updating playlist: " . mysqli_error($conn);
      }
      
      mysqli_stmt_close($stmt);
      mysqli_close($conn);
      break;
    case 'POST':

      $name =$requestBody->PlaylistName;
      $name = $_POST['PlaylistName'];
      $conn = mysqli_connect($servername, $username, $password,$db) or die("Connect failed: %s\n". $conn -> error);
      $sql = "INSERT INTO playlists (name,description,public) VALUES ('$name', 'description','true');";
        $result = mysqli_query($conn, $sql);
      echo "Created $name";
      break;
    case 'GET':
      $conn = mysqli_connect($servername, $username, $password,$db) or die("Connect failed: %s\n". $conn -> error);
      $sql = "SELECT id as PlaylistId,name as PlaylistName FROM playlists";
        $result = mysqli_query($conn, $sql);

        $userArr = array();
        while ($row = mysqli_fetch_assoc($result))
        {
            $userArr[] = $row;
        }
    
        echo json_encode($userArr,JSON_UNESCAPED_SLASHES);
      
      break;
      case strtoupper('delete'):
          $id=$uriData[2];
          $sql = "DELETE FROM playlists WHERE id = $id;";
          $conn = mysqli_connect($servername, $username, $password,$db) or die("Connect failed: ". $conn -> error);
          $result = mysqli_query($conn, $sql);
          echo "deleted";
        break;
    default:
    echo json_encode(
        array('message' => 'method unknown')
    );
      break;
  }

?>

