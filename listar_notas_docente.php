<?php
// Proporciona guías o estudiantes con notas para la pantalla docente.
session_start();
include("conexion.php");

header("Content-Type: application/json; charset=utf-8");

// Solo un docente autenticado puede consultar el listado.
if (!isset($_SESSION["id"]) || $_SESSION["rol"] !== "docente") {
    http_response_code(403);
    echo json_encode(["error" => "No tienes permiso para consultar notas."]);
    exit;
}

$grupo = $_GET["grupo"] ?? "";
$materia = $_GET["materia"] ?? "";
$guiaId = $_GET["guia_id"] ?? "";

if (!$grupo || !$materia) {
    http_response_code(400);
    echo json_encode(["error" => "Selecciona un grado y una materia."]);
    exit;
}

// Sin guía seleccionada, devuelve las guías disponibles para grado y materia.
if (!$guiaId) {

    $stmt = $conexion->prepare(
        "SELECT id, titulo
         FROM guias
         WHERE materia = ? AND grado = ?
         ORDER BY fecha_publicacion DESC"
    );

    $stmt->bind_param("ss", $materia, $grupo);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $guias = [];

    while ($fila = $resultado->fetch_assoc()) {
        $guias[] = $fila;
    }

    echo json_encode(["guias" => $guias], JSON_UNESCAPED_UNICODE);

    $stmt->close();
    $conexion->close();
    exit;
}

// Con una guía elegida, combina estudiantes y entregas; LEFT JOIN conserva
// también a quienes todavía no han enviado su trabajo.
$stmt = $conexion->prepare(
    "SELECT id, titulo
     FROM guias
     WHERE id = ? AND materia = ? AND grado = ?"
);

$stmt->bind_param("iss", $guiaId, $materia, $grupo);
$stmt->execute();

$resultado = $stmt->get_result();
$guia = $resultado->fetch_assoc();

$stmt->close();

if (!$guia) {
    http_response_code(404);
    echo json_encode(["error" => "La guía no existe."]);
    exit;
}

$stmt = $conexion->prepare(
    "SELECT
        usuarios.id AS estudiante_id,
        usuarios.nombre,
        usuarios.apellido,
        (
            SELECT AVG(entregas_promedio.nota)
            FROM entregas AS entregas_promedio
            INNER JOIN guias AS guias_promedio
                ON guias_promedio.id = entregas_promedio.guia_id
            WHERE entregas_promedio.estudiante_id = usuarios.id
              AND guias_promedio.materia = ?
              AND entregas_promedio.nota IS NOT NULL
        ) AS promedio_materia,
        entregas.id AS entrega_id,
        entregas.archivo,
        entregas.nota
     FROM usuarios
     LEFT JOIN entregas
        ON entregas.estudiante_id = usuarios.id
       AND entregas.guia_id = ?
     WHERE usuarios.rol = 'estudiante'
       AND usuarios.grado = ?
     ORDER BY usuarios.apellido, usuarios.nombre"
);

$stmt->bind_param("sis", $materia, $guiaId, $grupo);
$stmt->execute();

$resultado = $stmt->get_result();
$estudiantes = [];

while ($fila = $resultado->fetch_assoc()) {
    $estudiantes[] = $fila;
}

echo json_encode([
    "guia" => $guia,
    "estudiantes" => $estudiantes
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conexion->close();
?>