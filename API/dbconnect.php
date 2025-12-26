<?php
header('Content-Type:application/json; charset=utf-8');
$host='localhost';
$db = 'adise25_2021168';
require_once "db_upass.php";


if(gethostname()=='users.iee.ihu.gr') {
    $user=$DB_USER;
    $pass=$DB_PASS;
	$mysqli = new mysqli($host, $user, $pass, $db,null,'/home/student/iee/2021/iee2021168/mysql/run/mysql.sock');
} else {
    $user='root';
    $pass= '';
    $mysqli = new mysqli($host, $user, $pass, $db);
}

if ($mysqli->connect_errno) {
    echo json_encode([
        "status"=> "error",
        "message"=> "Failed connection to mysql",
        "code"=> $mysqli->connect_error
    ]);
    exit;
}

$result = $mysqli->query("SELECT NOW() AS c");
$row = $result->fetch_assoc();
echo json_encode([
    "status"=> "success",
    "time"=> $row['c']
    ]);