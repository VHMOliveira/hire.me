<?php

$dbHost     = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName     = "short_url";

try{
    $db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUsername, $dbPassword);
}catch(PDOException $e){
    echo "Connection failed: " . $e->getMessage();
}