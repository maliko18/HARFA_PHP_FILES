<?php
include './db_info.php';

function getDb(){
    global $host, $dbname, $username, $password;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        return $pdo;
    } catch (PDOException $e) {
        $empResponse = array(
            'status' => 'nok',
            'status_message' => 'db error'
        );
        echo json_encode($empResponse);
        die(null);
    }
}
?>