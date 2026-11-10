<?php

// Entrega en JSON los trabajos recibidos para la vista de seguimiento docente.
session_start();
include("conexion.php");

// Verificar sesión
if (!isset($_SESSION["id"])) {
    die("Debes iniciar sesión.");
}

// Verificar que sea docente
if ($_SESSION["rol"] !== "docente") {
    die("No tienes permiso para ver las entregas.");
}

// Combina la entrega con su guía y el nombre del estudiante que la envió.
$sql = "SELECT 
            entregas.id,
            entregas.guia_id,
            entregas.estudiante_id,
            entregas.archivo,
            entregas.fecha_entrega,
            guias.titulo,
            guias.materia,
            usuarios.nombre,
            usuarios.apellido
        FROM entregas
        INNER JOIN guias 
            ON entregas.guia_id = guias.id
        INNER JOIN usuarios 
            ON entregas.estudiante_id = usuarios.id
        ORDER BY entregas.fecha_entrega DESC";

$resultado = $conexion->query($sql);

$entregas = [];

while ($fila = $resultado->fetch_assoc()) {
    $entregas[] = $fila;
}

// El frontend consume la lista como JSON.
header("Content-Type: application/json");

echo json_encode($entregas);

$conexion->close();

?>