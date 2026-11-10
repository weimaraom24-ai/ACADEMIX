<?php

session_start();
include("conexion.php");

if (!isset($_SESSION["id"])) {
    die("Debes iniciar sesión.");
}

if ($_SESSION["rol"] !== "estudiante") {
    die("No tienes permiso para ver las calificaciones.");
}

$estudiante_id = $_SESSION["id"];

$sql = "SELECT
            entregas.nota,
            NULL AS comentario,
            entregas.fecha_entrega AS fecha_calificacion,
            guias.titulo,
            guias.materia
                FROM entregas
                INNER JOIN guias
                        ON entregas.guia_id = guias.id
                WHERE entregas.estudiante_id = ?
                    AND entregas.nota IS NOT NULL

        UNION ALL

        SELECT
            notas_estudiantes.nota,
            NULL AS comentario,
            NULL AS fecha_calificacion,
            'Nota del docente' AS titulo,
            notas_estudiantes.materia
        FROM notas_estudiantes
        WHERE notas_estudiantes.estudiante_id = ?

        ORDER BY fecha_calificacion DESC";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "No se pudieron consultar las calificaciones."]);
    $conexion->close();
    exit;
}

$stmt->bind_param("ii", $estudiante_id, $estudiante_id);

$stmt->execute();

$resultado = $stmt->get_result();

$calificaciones = [];

while ($fila = $resultado->fetch_assoc()) {
    $calificaciones[] = $fila;
}

header("Content-Type: application/json");

echo json_encode($calificaciones);

$stmt->close();
$conexion->close();

?>