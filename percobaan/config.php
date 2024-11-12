<?php
// config.php

// Koneksi ke database
$host = "localhost";
$username = "root";
$password = "";
$database = "tokobaju";

$config = new mysqli($host, $username, $password, $database);

// Periksa koneksi
if ($config->connect_error) {
    die("Connection failed: " . $config->connect_error);
}

// Fungsi untuk mempermudah penggunaan prepared statements
function prepareQuery($query) {
    global $config;
    return $config->prepare($query);
}

// Fungsi untuk menutup koneksi saat tidak lagi dibutuhkan
function closeConnection() {
    global $config;
    $config->close();
}

?>
