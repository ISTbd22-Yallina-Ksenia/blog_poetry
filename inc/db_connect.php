<?php
session_start();
$session = $_SESSION;
$request = $_REQUEST;

// Загрузка необходимой конфигурации из JSON файла
$configFile = __DIR__.'/config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    $db_host = $config['database']['host'];
    $db_user = $config['database']['username'];
    $db_pass = $config['database']['password'];
    $db_name = $config['database']['name'];
} 

define('DB_HOST', $db_host);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_NAME', $db_name);

$opts = array(
    'user' => DB_USER,
    'pass' => DB_PASS,
    'db'   => DB_NAME
);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Database connection error');
}
?>