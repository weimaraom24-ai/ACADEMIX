<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include("conexion.php");

function volverGuias(string $estado): void
{
    header("Location: guias.html?entrega=" . rawurlencode($estado));
    exit;
}

if (!isset($_SESSION["id"]) || ($_SESSION["rol"] ?? "") !== "estudiante") {
    header("Location: index.html");
    exit;
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    volverGuias("solicitud_invalida");
}

$guiaId = filter_var($_POST["guia_id"] ?? null, FILTER_VALIDATE_INT);
$estudianteId = (int)$_SESSION["id"];
if (!$guiaId) {
    volverGuias("guia_invalida");
}

if (!isset($_FILES["archivo"]) || $_FILES["archivo"]["error"] !== UPLOAD_ERR_OK) {
    volverGuias("archivo_requerido");
}

$archivo = $_FILES["archivo"];
$extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));
if (!in_array($extension, ["pdf", "doc", "docx"], true)) {
    volverGuias("formato_invalido");
}
if ($archivo["size"] > 10 * 1024 * 1024) {
    volverGuias("archivo_grande");
}

$consultaGuia = $conexion->prepare("SELECT grado FROM guias WHERE id = ?");
$consultaGuia->bind_param("i", $guiaId);
$consultaGuia->execute();
$guia = $consultaGuia->get_result()->fetch_assoc();
$consultaGuia->close();
if (!$guia) {
    volverGuias("guia_invalida");
}

$consultaEstudiante = $conexion->prepare("SELECT grado FROM usuarios WHERE id = ? AND rol = 'estudiante'");
$consultaEstudiante->bind_param("i", $estudianteId);
$consultaEstudiante->execute();
$estudiante = $consultaEstudiante->get_result()->fetch_assoc();
$consultaEstudiante->close();
if (!$estudiante) {
    volverGuias("estudiante_invalido");
}
if (!empty($guia["grado"]) && !empty($estudiante["grado"]) && $guia["grado"] !== $estudiante["grado"]) {
    volverGuias("grado_no_coincide");
}

$consultaEntrega = $conexion->prepare(
    "SELECT id FROM entregas WHERE guia_id = ? AND estudiante_id = ? LIMIT 1"
);
$consultaEntrega->bind_param("ii", $guiaId, $estudianteId);
$consultaEntrega->execute();
$yaEntrego = $consultaEntrega->get_result()->num_rows > 0;
$consultaEntrega->close();
if ($yaEntrego) {
    volverGuias("ya_entregada");
}

$directorio = __DIR__ . "/uploads/entregas/";
if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
    volverGuias("error_guardado");
}

$nombreArchivo = bin2hex(random_bytes(16)) . "." . $extension;
$rutaFisica = $directorio . $nombreArchivo;
$rutaBaseDatos = "uploads/entregas/" . $nombreArchivo;
if (!move_uploaded_file($archivo["tmp_name"], $rutaFisica)) {
    volverGuias("error_guardado");
}

$insertar = $conexion->prepare(
    "INSERT INTO entregas (guia_id, estudiante_id, archivo, fecha_entrega)
     VALUES (?, ?, ?, NOW())"
);
$insertar->bind_param("iis", $guiaId, $estudianteId, $rutaBaseDatos);
if (!$insertar->execute()) {
    $insertar->close();
    if (is_file($rutaFisica)) {
        unlink($rutaFisica);
    }
    volverGuias("error_guardado");
}

$insertar->close();
$conexion->close();
volverGuias("ok");