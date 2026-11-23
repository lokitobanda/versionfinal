<?php
$conexion = new mysqli("localhost", "root", "", "skygames");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "Conectado correctamente a la base de datos";
?>