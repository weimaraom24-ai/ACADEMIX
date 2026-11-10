<?php

// Devuelve al cliente las guías ordenadas desde la publicación más reciente.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include("conexion.php");

// Para estudiantes agrega su entrega más reciente; docentes conservan el listado general.
if (isset($_SESSION["id"]) && ($_SESSION["rol"] ?? "") === "estudiante") {
    $estudiante = $conexion->prepare("SELECT grado FROM usuarios WHERE id = ? AND rol = 'estudiante'");
    $estudiante->bind_param("i", $_SESSION["id"]);
    $estudiante->execute();
    $filaEstudiante = $estudiante->get_result()->fetch_assoc();
    $estudiante->close();
    $gradoEstudiante = $filaEstudiante["grado"] ?? "";

    $sql = "SELECT guias.*,
                   entregas_recientes.id AS entrega_id,
                   entregas_recientes.fecha_entrega,
                   entregas_recientes.nota AS nota_entrega
            FROM guias
            LEFT JOIN (
                SELECT guia_id, MAX(id) AS entrega_id
                FROM entregas
                WHERE estudiante_id = ?
                GROUP BY guia_id
            ) AS ultimas_entregas ON ultimas_entregas.guia_id = guias.id
            LEFT JOIN entregas AS entregas_recientes ON entregas_recientes.id = ultimas_entregas.entrega_id
                WHERE (? = '' OR guias.grado IS NULL OR guias.grado = '' OR guias.grado = ?)
            ORDER BY guias.fecha_publicacion DESC";
    $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iss", $_SESSION["id"], $gradoEstudiante, $gradoEstudiante);
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $resultado = $conexion->query("SELECT * FROM guias ORDER BY fecha_publicacion DESC");
}

$guias = [];

while ($fila = $resultado->fetch_assoc()) {
    $guias[] = $fila;
}

// La respuesta JSON alimenta las listas del estudiante y del docente.
header("Content-Type: application/json");

echo json_encode($guias);

if (isset($stmt)) {
    $stmt->close();
}
$conexion->close();

?>