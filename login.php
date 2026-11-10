```php
<?php

session_start();

include("conexion.php");

$correo = $_POST["correo"];
$password = $_POST["password"];
$rol = $_POST["rol"];

$sql = "SELECT * FROM usuarios WHERE correo = ? AND rol = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $correo, $rol);

// CORRECCIÓN: ejecutar la consulta sobre el objeto $stmt.
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows == 1) {

    $usuario = $resultado->fetch_assoc();

    if (isset($usuario["activo"]) && (int)$usuario["activo"] !== 1) {
        echo "Esta cuenta está desactivada. Comunícate con administración.";
    } elseif (password_verify($password, $usuario["password"])) {

        $_SESSION["id"] = $usuario["id"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["rol"] = $usuario["rol"];

        if ($rol == "estudiante") {
            header("Location: estudiante.html");
            exit();
        }

        if ($rol == "docente") {
            header("Location: docente.html");
            exit();
        }

        if ($rol == "admin") {
            header("Location: admin.php");
            exit();
        }

    } else {
        echo "Contraseña incorrecta";
    }

} else {
    echo "Correo o rol incorrecto";
}

$stmt->close();
$conexion->close();

?>
```