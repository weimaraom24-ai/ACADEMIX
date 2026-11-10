<?php
// Abre la sesión que se debe cerrar y elimina todos sus datos almacenados.
session_start();
$_SESSION = [];

// Invalida también la cookie del navegador que identifica esta sesión.
if (ini_get("session.use_cookies")) {
    $parametros = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parametros["path"],
        $parametros["domain"],
        $parametros["secure"],
        $parametros["httponly"]
    );
}

// Destruye la sesión y vuelve a la pantalla pública de acceso.
session_destroy();
header("Location: index.html");
exit;
?>