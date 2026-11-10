<?php
// Recibe una guía del docente, almacena su archivo y registra sus metadatos.
session_start();
include("conexion.php");

// Rechaza solicitudes sin sesión docente válida.
if (!isset($_SESSION["id"]) || $_SESSION["rol"] !== "docente") {
    die("No tienes permiso para subir guías.");
}

$titulo = $_POST["titulo"] ?? "";
$materia = $_POST["materia"] ?? "";
$grado = $_POST["grado"] ?? "";
$descripcion = $_POST["descripcion"] ?? "";
$fecha_limite = $_POST["fecha_limite"] ?? "";

if (!$titulo || !$materia || !$grado || !$fecha_limite || !isset($_FILES["archivo"])) {
    die("Faltan datos obligatorios.");
}

// Genera un nombre de archivo distinto y prepara la carpeta de destino.
$archivo = $_FILES["archivo"];

$nombreArchivo = basename($archivo["name"]);
$nombreSeguro = time() . "_" . $nombreArchivo;

$carpeta = "uploads/guias/";

if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

$ruta = $carpeta . $nombreSeguro;

if (!move_uploaded_file($archivo["tmp_name"], $ruta)) {
    die("No se pudo subir el archivo.");
}

// Registra el material y su propietario en la tabla de guías.
$stmt = $conexion->prepare(
    "INSERT INTO guias
    (titulo, materia, grado, descripcion, archivo, fecha_publicacion, fecha_limite, docente_id)
    VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)"
);

$docente_id = $_SESSION["id"];

$stmt->bind_param(
    "ssssssi",
    $titulo,
    $materia,
    $grado,
    $descripcion,
    $ruta,
    $fecha_limite,
    $docente_id
);

if ($stmt->execute()) {
    header("Location: guiasD.html");
    exit;
}

echo "Error al guardar la guía: " . $stmt->error;

$stmt->close();
$conexion->close();
?>