<?php
$config = require __DIR__ . '/config.php';

$conn = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_password'],
    $config['db_name']
);

if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}

$conn->set_charset('utf8mb4');
