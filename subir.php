<?php
// Valida y guarda un archivo de materia; luego presenta el resultado al usuario.
$materiasPermitidas = [
    'Lectura Critica',
    'Matematicas',
    'Ciencias Naturales',
    'Competencias Ciudadanas',
    'Ingles'
];
$extensionesPermitidas = ['pdf', 'doc', 'docx'];
$maximoBytes = 10 * 1024 * 1024;
$mensaje = '';
$exito = false;

// Verifica método, campos, tipo de archivo y límite de tamaño antes de guardar.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $mensaje = 'Solicitud no valida.';
} elseif (!isset($_POST['materia'], $_FILES['archivo'])) {
    $mensaje = 'Selecciona una materia y un archivo.';
} else {
    $materia = trim($_POST['materia']);
    $archivo = $_FILES['archivo'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($materia, $materiasPermitidas, true)) {
        $mensaje = 'La materia seleccionada no es valida.';
    } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'No se pudo recibir el archivo.';
    } elseif ($archivo['size'] > $maximoBytes) {
        $mensaje = 'El archivo no puede superar los 10 MB.';
    } elseif (!in_array($extension, $extensionesPermitidas, true)) {
        $mensaje = 'Solo se permiten archivos PDF, DOC y DOCX.';
    } else {
        // Crea el directorio si hace falta y genera un nombre seguro no repetido.
        $directorio = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $nombreSeguro = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($archivo['name']));
        $rutaDestino = $directorio . DIRECTORY_SEPARATOR . $nombreSeguro;

        if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            $exito = true;
            $mensaje = 'Archivo de ' . htmlspecialchars($materia, ENT_QUOTES, 'UTF-8') . ' subido correctamente.';
        } else {
            $mensaje = 'No se pudo guardar el archivo.';
        }
    }
}
?>
<!-- La plantilla comunica el resultado y permite volver al panel de guías. -->
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Resultado de carga</title>
  <link rel="stylesheet" href="estudiante.css">
</head>
<body>
  <main class="container upload-result">
    <section class="hero-card">
      <h1><?= $exito ? 'Carga completada' : 'No se pudo subir el archivo' ?></h1>
      <p class="lead"><?= $mensaje ?></p>
    <a class="btn-primary" href="guiasD.html">Volver a Guías</a>
    </section>
  </main>
</body>
</html>
