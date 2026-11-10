<?php

// Procesa el alta de estudiantes y docentes enviadas desde el formulario.
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $rol = $_POST["rol"];
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $correo = $_POST["correo"];
    $documento = $_POST["documento"];
    $password = $_POST["password"];
    $grupo = $_POST["grupo"] ?? null;
    $materia = $_POST["materia"] ?? null;

    // Limita los grados aceptados a los grupos habilitados en la plataforma.
    $gruposPermitidos = [
        "10-1", "10-2", "10-3", "10-4", "10-5", "10-6", "10-7",
        "11-1", "11-2", "11-3", "11-4", "11-5", "11-6", "11-7"
    ];

    if ($rol === "estudiante" && !in_array($grupo, $gruposPermitidos, true)) {
        die("Selecciona un grupo válido.");
    }

    if ($rol !== "estudiante") {
        $grupo = null;
    }

    if ($rol !== "docente") {
    $materia = null;
}

    // El rol administrador se crea por una vía controlada, no desde registro público.
    if ($rol === "admin") {
        die("No está permitido registrarse como administrador.");
    }

    // Nunca guardar la contraseña original; almacenar un hash verificable.
    $password_segura = password_hash($password, PASSWORD_DEFAULT);

    // Impide duplicar cuentas con el mismo correo antes de insertar.
    $verificar = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $verificar->bind_param("s", $correo);
    $verificar->execute();
    $resultado = $verificar->get_result();

    if ($resultado->num_rows > 0) {

        echo "Este correo ya está registrado.";

    } else {

        // Inserta los datos del usuario con parámetros preparados.
        $sql = $conexion->prepare(
    "INSERT INTO usuarios 
    (rol, nombre, apellido, correo, documento, password, grado, materia) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

       $sql->bind_param(
    "ssssssss",
    $rol,
    $nombre,
    $apellido,
    $correo,
    $documento,
    $password_segura,
    $grupo,
    $materia
);

        if ($sql->execute()) {
            // Define el destino posterior al registro según el rol creado.
            $pagina_destino = $rol === "estudiante" ? "estudiante.html" : "docente.html";
            $nombre_mostrado = htmlspecialchars($nombre, ENT_QUOTES, "UTF-8");

            echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro exitoso | ACADEMIX</title>
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: #19234a;
            background: linear-gradient(135deg, #172a5a, #4453c3);
        }
        .exito-card {
            width: min(430px, 100%);
            padding: 42px 32px;
            text-align: center;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 28px;
            box-shadow: 0 24px 70px rgba(4, 12, 30, 0.3);
            animation: aparecer 500ms ease both;
        }
        .check {
            width: 76px;
            height: 76px;
            margin: 0 auto 22px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            color: #fff;
            font-size: 42px;
            background: #20a779;
            animation: celebrar 700ms ease 250ms both;
        }
        h1 { margin: 0 0 12px; font-size: 1.9rem; }
        p { margin: 8px 0; color: #596582; line-height: 1.6; }
        strong { color: #19234a; }
        .barra { height: 6px; margin-top: 26px; overflow: hidden; border-radius: 99px; background: #e7eaf4; }
        .barra::after { content: ""; display: block; width: 100%; height: 100%; background: #4453c3; transform-origin: left; animation: avanzar 4s linear both; }
        @keyframes aparecer { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes celebrar { from { transform: scale(0); } 70% { transform: scale(1.12); } to { transform: scale(1); } }
        @keyframes avanzar { from { transform: scaleX(0); } to { transform: scaleX(1); } }
    </style>
</head>
<body>
    <main class="exito-card">
        <div class="check" aria-hidden="true">✓</div>
        <h1>¡Registro exitoso!</h1>
        <p>Bienvenido a ACADEMIX, <strong>{$nombre_mostrado}</strong>.</p>
        <p>Te estamos llevando a tu página principal.</p>
        <div class="barra" aria-hidden="true"></div>
    </main>
    <script>
        setTimeout(function () {
            window.location.href = "{$pagina_destino}";
        }, 4000);
    </script>
</body>
</html>
HTML;
        } else {
            echo "Error al registrar: " . $conexion->error;
        }

        $sql->close();
    }

    $verificar->close();
    $conexion->close();
}

?>
