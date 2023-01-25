<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Methods: GET");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $fp = fopen('logs.txt', 'r'); 
        if ($fp) {
            $arrayEscaped = explode("\n", fread($fp, filesize('logs.txt')));
        }
        fclose($fp);

        $arrayReplaced = str_replace('\,', ',', $arrayEscaped );
        $arrayLength = count($arrayReplaced);
        $data = array();

        $lastFromArray = 10; //tahan võtta viimased x arv sisestusi
        $indexValue = ($arrayLength-1)-$lastFromArray;
        for ($x = 0; $x < $lastFromArray; $x++) {
            array_push($data,$arrayReplaced[$indexValue]);
            $indexValue = $indexValue + 1;
        }

        echo json_encode($data);
        
        break;
    default:
        echo json_encode(
            array("message" => 'error, request did not go through')
        );
        break;
}
?>
