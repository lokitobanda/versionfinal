<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conexion = new mysqli("localhost", "root", "", "skygames_db");

if ($conexion->connect_error) {
    die("Error: " . $conexion->connect_error);
}

echo "Conexión exitosa con MySQL";
?>