<?php
//require_once 'vendor/autoload.php';
header('Content-type: text/html; charset=utf-8');
$objectId = $_GET['objectId'];
$time = time();
$rootPath = $_SERVER['DOCUMENT_ROOT'];

echo "$objectId\r\n";

$objects = json_decode(file_get_contents("config.json"))->objects;
$object = $objects[$objectId - 1];
var_dump($object);
file_put_contents($rootPath . "/storage/$object->id.json", json_encode([
    'time' => $time
]));

//$logFilePath = $rootPath.'/storage/log.txt';
//$str = file_get_contents($logFilePath);
//$str .= "Обновление времени у объекта $objectId: " . date('H:i:s', $time) . "\r\n";
//file_put_contents($logFilePath, $str);