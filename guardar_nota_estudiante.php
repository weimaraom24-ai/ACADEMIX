<?php
// Guarda o actualiza la nota de una entrega desde la interfaz docente.
session_start();
include("conexion.php");

header("Content-Type: application/json; charset=utf-8");

// Valida el rol antes de aceptar una modificación de calificación.
if (!isset($_SESSION["id"]) || $_SESSION["rol"] !== "docente") {
    http_response_code(403);
    echo json_encode(["error" => "No tienes permiso para modificar notas."]);
    exit;
}

// Comprueba el identificador y el rango antes de escribir en la base de datos.
$entregaId = filter_input(INPUT_POST, "entrega_id", FILTER_VALIDATE_INT);
$notaTexto = $_POST["nota"] ?? "";

if (!$entregaId) {
    http_response_code(400);
    echo json_encode(["error" => "Entrega no válida."]);
    exit;
}

if (!is_numeric($notaTexto) || (float)$notaTexto < 0 || (float)$notaTexto > 5) {
    http_response_code(400);
    echo json_encode(["error" => "La nota debe estar entre 0.0 y 5.0."]);
    exit;
}

$nota = round((float)$notaTexto, 1);

// Actualiza únicamente la entrega indicada usando una consulta preparada.
$stmt = $conexion->prepare(
    "UPDATE entregas
     SET nota = ?
     WHERE id = ?"
);

$stmt->bind_param("di", $nota, $entregaId);

if ($stmt->execute()) {
    echo json_encode([
        "ok" => true,
        "nota" => number_format($nota, 1, ".", "")
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar la nota."]);
}

$stmt->close();
$conexion->close();
?>