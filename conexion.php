<?php

// Conexión MySQL compartida por los endpoints PHP del proyecto.
mysqli_report(MYSQLI_REPORT_OFF);

// Conecta ACADEMIX con la base de datos de InfinityFree.
$conexion = new mysqli(
    "sql104.infinityfree.com", // Servidor
    "if0_43187896",            // Usuario
    "W18YlJW4cvIZweg",                 // Contraseña
    "if0_43187896_academix",   // Base de datos
    3306                       // Puerto
);

// Comprueba si ocurrió un error de conexión.
if ($conexion->connect_error) {
    die("No se pudo conectar con MySQL. Detalle: " . $conexion->connect_error);
}

// Utiliza caracteres UTF-8 para evitar problemas con tildes y ñ.
$conexion->set_charset("utf8mb4");
?>