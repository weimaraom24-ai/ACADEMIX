<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (!isset($_SESSION["id"]) || ($_SESSION["rol"] ?? "") !== "admin") {
    header("Location: index.html");
    exit;
}

include("conexion.php");

$comprobarAdmin = $conexion->prepare(
  "SELECT activo FROM usuarios WHERE id = ? AND rol = 'admin' LIMIT 1"
);
$comprobarAdmin->bind_param("i", $_SESSION["id"]);
$comprobarAdmin->execute();
$adminActual = $comprobarAdmin->get_result()->fetch_assoc();
$comprobarAdmin->close();

if (!$adminActual || (int)$adminActual["activo"] !== 1) {
  header("Location: logout.php");
  exit;
}

function adminHtml($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function adminTotal(mysqli $conexion, string $sql): int
{
    $resultado = $conexion->query($sql);
    if (!$resultado) {
        return 0;
    }

    $fila = $resultado->fetch_assoc();
    return (int)($fila["total"] ?? 0);
}

  function adminRedirect(string $mensaje, string $tipo = "success"): void
  {
    $_SESSION["admin_flash"] = ["mensaje" => $mensaje, "tipo" => $tipo];
    header("Location: admin.php?view=usuarios");
    exit;
  }

  if (empty($_SESSION["admin_csrf"])) {
    $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
  }
  $csrfToken = $_SESSION["admin_csrf"];

  if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $tokenRecibido = $_POST["csrf_token"] ?? "";
    if (!is_string($tokenRecibido) || !hash_equals($csrfToken, $tokenRecibido)) {
      adminRedirect("La sesión del formulario venció. Inténtalo otra vez.", "error");
    }

    $accion = $_POST["accion"] ?? "";
    $rolesPermitidos = ["estudiante", "docente", "admin"];
    $gradosPermitidos = [
      "10-1", "10-2", "10-3", "10-4", "10-5", "10-6", "10-7",
      "11-1", "11-2", "11-3", "11-4", "11-5", "11-6", "11-7"
    ];
    $materiasPermitidas = [
      "Lectura Critica", "Matematicas", "Ciencias Naturales",
      "Competencias Ciudadanas", "Ingles"
    ];

    if ($accion === "crear" || $accion === "actualizar") {
      $usuarioId = filter_var($_POST["usuario_id"] ?? null, FILTER_VALIDATE_INT);
      $nombre = trim($_POST["nombre"] ?? "");
      $apellido = trim($_POST["apellido"] ?? "");
      $correo = trim($_POST["correo"] ?? "");
      $documento = trim($_POST["documento"] ?? "");
      $rol = $_POST["rol"] ?? "";
      $grado = $_POST["grado"] ?? "";
      $materia = $_POST["materia"] ?? "";
      $password = $_POST["password"] ?? "";

      if ($nombre === "" || $apellido === "" || $documento === "" ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
        !in_array($rol, $rolesPermitidos, true)) {
        adminRedirect("Completa los datos y selecciona un rol válido.", "error");
      }

      if (($rol === "estudiante" && !in_array($grado, $gradosPermitidos, true)) ||
        ($rol === "docente" && !in_array($materia, $materiasPermitidas, true))) {
        adminRedirect("El grado o la materia no corresponden al rol seleccionado.", "error");
      }

      $grado = $rol === "estudiante" ? $grado : null;
      $materia = $rol === "docente" ? $materia : null;

      if ($accion === "crear") {
        if (strlen($password) < 8) {
          adminRedirect("La contraseña debe tener al menos 8 caracteres.", "error");
        }

        $duplicado = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ? OR documento = ? LIMIT 1");
        $duplicado->bind_param("ss", $correo, $documento);
        $duplicado->execute();
        if ($duplicado->get_result()->num_rows > 0) {
          $duplicado->close();
          adminRedirect("Ya existe una cuenta con ese correo o documento.", "error");
        }
        $duplicado->close();

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insertar = $conexion->prepare(
          "INSERT INTO usuarios (rol, activo, materia, grado, nombre, apellido, correo, documento, password)
           VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insertar->bind_param("ssssssss", $rol, $materia, $grado, $nombre, $apellido, $correo, $documento, $passwordHash);
        if (!$insertar->execute()) {
          $insertar->close();
          adminRedirect("No se pudo crear la cuenta. Revisa que correo y documento sean únicos.", "error");
        }
        $insertar->close();
        adminRedirect("La cuenta fue creada correctamente.");
      }

      if (!$usuarioId) {
        adminRedirect("La cuenta seleccionada no es válida.", "error");
      }

      $actual = $conexion->prepare("SELECT rol, activo FROM usuarios WHERE id = ?");
      $actual->bind_param("i", $usuarioId);
      $actual->execute();
      $usuarioActual = $actual->get_result()->fetch_assoc();
      $actual->close();
      if (!$usuarioActual) {
        adminRedirect("La cuenta ya no existe.", "error");
      }

      if ($usuarioId === (int)$_SESSION["id"] && $rol !== "admin") {
        adminRedirect("No puedes cambiar tu propio rol de administrador.", "error");
      }

      if ($usuarioActual["rol"] === "admin" && (int)$usuarioActual["activo"] === 1 && $rol !== "admin") {
        $adminsActivos = adminTotal($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin' AND activo = 1");
        if ($adminsActivos <= 1) {
          adminRedirect("No puedes cambiar el rol del último administrador activo.", "error");
        }
      }

      $duplicado = $conexion->prepare("SELECT id FROM usuarios WHERE (correo = ? OR documento = ?) AND id <> ? LIMIT 1");
      $duplicado->bind_param("ssi", $correo, $documento, $usuarioId);
      $duplicado->execute();
      if ($duplicado->get_result()->num_rows > 0) {
        $duplicado->close();
        adminRedirect("Otro usuario ya tiene ese correo o documento.", "error");
      }
      $duplicado->close();

      if ($password !== "" && strlen($password) < 8) {
        adminRedirect("La nueva contraseña debe tener al menos 8 caracteres.", "error");
      }

      if ($password === "") {
        $actualizar = $conexion->prepare(
          "UPDATE usuarios SET nombre = ?, apellido = ?, correo = ?, documento = ?, rol = ?, grado = ?, materia = ? WHERE id = ?"
        );
        $actualizar->bind_param("sssssssi", $nombre, $apellido, $correo, $documento, $rol, $grado, $materia, $usuarioId);
      } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $actualizar = $conexion->prepare(
          "UPDATE usuarios SET nombre = ?, apellido = ?, correo = ?, documento = ?, rol = ?, grado = ?, materia = ?, password = ? WHERE id = ?"
        );
        $actualizar->bind_param("ssssssssi", $nombre, $apellido, $correo, $documento, $rol, $grado, $materia, $passwordHash, $usuarioId);
      }

      if (!$actualizar->execute()) {
        $actualizar->close();
        adminRedirect("No se pudieron guardar los cambios.", "error");
      }
      $actualizar->close();
      adminRedirect("Los datos del usuario fueron actualizados.");
    }

    if ($accion === "alternar_estado") {
      $usuarioId = filter_var($_POST["usuario_id"] ?? null, FILTER_VALIDATE_INT);
      if (!$usuarioId || $usuarioId === (int)$_SESSION["id"]) {
        adminRedirect("No puedes desactivar tu propia cuenta.", "error");
      }

      $consultaUsuario = $conexion->prepare("SELECT rol, activo FROM usuarios WHERE id = ?");
      $consultaUsuario->bind_param("i", $usuarioId);
      $consultaUsuario->execute();
      $usuarioObjetivo = $consultaUsuario->get_result()->fetch_assoc();
      $consultaUsuario->close();
      if (!$usuarioObjetivo) {
        adminRedirect("La cuenta seleccionada no existe.", "error");
      }

      if ($usuarioObjetivo["rol"] === "admin" && (int)$usuarioObjetivo["activo"] === 1) {
        $adminsActivos = adminTotal($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin' AND activo = 1");
        if ($adminsActivos <= 1) {
          adminRedirect("No puedes desactivar al último administrador activo.", "error");
        }
      }

      $nuevoEstado = (int)$usuarioObjetivo["activo"] === 1 ? 0 : 1;
      $cambiarEstado = $conexion->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
      $cambiarEstado->bind_param("ii", $nuevoEstado, $usuarioId);
      if (!$cambiarEstado->execute()) {
        $cambiarEstado->close();
        adminRedirect("No se pudo cambiar el estado de la cuenta.", "error");
      }
      $cambiarEstado->close();
      adminRedirect($nuevoEstado === 1 ? "La cuenta fue reactivada." : "La cuenta fue desactivada.");
    }

    if ($accion === "eliminar") {
      $usuarioId = filter_var($_POST["usuario_id"] ?? null, FILTER_VALIDATE_INT);
      if (!$usuarioId || $usuarioId === (int)$_SESSION["id"]) {
        adminRedirect("No puedes eliminar tu propia cuenta.", "error");
      }

      $conexion->begin_transaction();
      $consultaUsuario = $conexion->prepare("SELECT rol, activo FROM usuarios WHERE id = ? FOR UPDATE");
      $consultaUsuario->bind_param("i", $usuarioId);
      $consultaUsuario->execute();
      $usuarioObjetivo = $consultaUsuario->get_result()->fetch_assoc();
      $consultaUsuario->close();
      if (!$usuarioObjetivo) {
        $conexion->rollback();
        adminRedirect("La cuenta seleccionada ya no existe.", "error");
      }

      if ($usuarioObjetivo["rol"] === "admin" && (int)$usuarioObjetivo["activo"] === 1) {
        $adminsActivos = adminTotal($conexion, "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin' AND activo = 1");
        if ($adminsActivos <= 1) {
          $conexion->rollback();
          adminRedirect("No puedes eliminar al último administrador activo.", "error");
        }
      }

      $dependencias = 0;
      if ($usuarioObjetivo["rol"] === "estudiante") {
        $verificarHistorial = $conexion->prepare(
          "SELECT
             (SELECT COUNT(*) FROM entregas WHERE estudiante_id = ?) +
             (SELECT COUNT(*) FROM notas_estudiantes WHERE estudiante_id = ?) AS total"
        );
        $verificarHistorial->bind_param("ii", $usuarioId, $usuarioId);
      } elseif ($usuarioObjetivo["rol"] === "docente") {
        $verificarHistorial = $conexion->prepare(
          "SELECT
             (SELECT COUNT(*) FROM guias WHERE docente_id = ?) +
             (SELECT COUNT(*) FROM notas_estudiantes WHERE docente_id = ?) AS total"
        );
        $verificarHistorial->bind_param("ii", $usuarioId, $usuarioId);
      }

      if (isset($verificarHistorial)) {
        $verificarHistorial->execute();
        $dependencias = (int)($verificarHistorial->get_result()->fetch_assoc()["total"] ?? 0);
        $verificarHistorial->close();
      }

      if ($dependencias > 0) {
        $conexion->rollback();
        adminRedirect("No se puede eliminar: esta cuenta tiene historial académico. Puedes desactivarla para conservarlo.", "error");
      }

      $eliminarUsuario = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
      $eliminarUsuario->bind_param("i", $usuarioId);
      if (!$eliminarUsuario->execute()) {
        $eliminarUsuario->close();
        $conexion->rollback();
        adminRedirect("No se pudo eliminar la cuenta.", "error");
      }
      $eliminarUsuario->close();
      $conexion->commit();
      adminRedirect("La cuenta fue eliminada permanentemente.");
    }

    adminRedirect("La acción solicitada no es válida.", "error");
  }

  $mensajeFlash = $_SESSION["admin_flash"] ?? null;
  unset($_SESSION["admin_flash"]);

$vistasPermitidas = ["dashboard", "usuarios", "notas", "reportes", "configuracion"];
$vista = $_GET["view"] ?? "dashboard";
if (!in_array($vista, $vistasPermitidas, true)) {
    $vista = "dashboard";
}

$resumenUsuarios = $conexion->query(
    "SELECT COUNT(*) AS total,
            SUM(rol = 'estudiante') AS estudiantes,
            SUM(rol = 'docente') AS docentes
     FROM usuarios"
);
$filaUsuarios = $resumenUsuarios ? $resumenUsuarios->fetch_assoc() : [];
$totalUsuarios = (int)($filaUsuarios["total"] ?? 0);
$totalEstudiantes = (int)($filaUsuarios["estudiantes"] ?? 0);
$totalDocentes = (int)($filaUsuarios["docentes"] ?? 0);
$totalGuias = adminTotal($conexion, "SELECT COUNT(*) AS total FROM guias");
$totalEntregas = adminTotal($conexion, "SELECT COUNT(*) AS total FROM entregas");
$totalNotasEntregas = adminTotal(
    $conexion,
    "SELECT COUNT(*) AS total FROM entregas WHERE nota IS NOT NULL"
);
$totalNotasDirectas = adminTotal(
  $conexion,
  "SELECT COUNT(*) AS total FROM notas_estudiantes"
);
$totalNotas = $totalNotasEntregas + $totalNotasDirectas;
$promedioNotas = null;
$resultadoPromedio = $conexion->query(
  "SELECT AVG(nota) AS promedio FROM (
    SELECT nota FROM entregas WHERE nota IS NOT NULL
    UNION ALL
    SELECT nota FROM notas_estudiantes
  ) AS notas_todas"
);
if ($resultadoPromedio) {
    $filaPromedio = $resultadoPromedio->fetch_assoc();
    $promedioNotas = $filaPromedio["promedio"] !== null
        ? number_format((float)$filaPromedio["promedio"], 1)
        : null;
}

$titulos = [
    "dashboard" => "Resumen",
    "usuarios" => "Usuarios",
    "notas" => "Calificaciones",
    "reportes" => "Reportes",
    "configuracion" => "Configuración"
];
$tituloVista = $titulos[$vista];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Administración de la plataforma educativa ACADEMIX">
  <title><?= adminHtml($tituloVista) ?> | ACADEMIX</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-page">
  <div class="contenedor admin-layout">
    <aside class="sidebar admin-sidebar">
      <a class="admin-brand" href="admin.php" aria-label="Inicio de administración ACADEMIX">
        <img src="logo.jpg" alt="">
        <span>ACADEMIX</span>
      </a>
      <p class="admin-sidebar-label">Administración</p>
      <nav aria-label="Menú de administración">
        <ul>
          <li class="<?= $vista === "dashboard" ? "active" : "" ?>"><a href="admin.php"><i data-lucide="layout-dashboard" aria-hidden="true"></i><span>Resumen</span></a></li>
          <li class="<?= $vista === "usuarios" ? "active" : "" ?>"><a href="admin.php?view=usuarios"><i data-lucide="users" aria-hidden="true"></i><span>Usuarios</span></a></li>
          <li class="<?= $vista === "notas" ? "active" : "" ?>"><a href="admin.php?view=notas"><i data-lucide="clipboard-list" aria-hidden="true"></i><span>Calificaciones</span></a></li>
          <li class="<?= $vista === "reportes" ? "active" : "" ?>"><a href="admin.php?view=reportes"><i data-lucide="chart-no-axes-column-increasing" aria-hidden="true"></i><span>Reportes</span></a></li>
          <li class="<?= $vista === "configuracion" ? "active" : "" ?>"><a href="admin.php?view=configuracion"><i data-lucide="settings-2" aria-hidden="true"></i><span>Configuración</span></a></li>
          <li class="sidebar-logout"><a href="logout.php"><i data-lucide="log-out" aria-hidden="true"></i><span>Cerrar sesión</span></a></li>
        </ul>
      </nav>
    </aside>

    <main class="contenido admin-content">
      <header class="admin-heading">
        <div>
          <p class="admin-eyebrow">ACADEMIX / ADMINISTRACIÓN</p>
          <h1><?= adminHtml($tituloVista) ?></h1>
          <p class="admin-subtitle">Hola, <?= adminHtml($_SESSION["nombre"] ?? "administrador") ?>. Aquí tienes el estado actual de la plataforma.</p>
        </div>
        <span class="admin-role">ADMIN</span>
      </header>

      <?php if ($vista === "dashboard"): ?>
        <section class="admin-metrics" aria-label="Indicadores principales">
          <article class="metric metric-users"><span>Usuarios registrados</span><strong><?= $totalUsuarios ?></strong><small><?= $totalEstudiantes ?> estudiantes · <?= $totalDocentes ?> docentes</small></article>
          <article class="metric metric-guides"><span>Guías publicadas</span><strong><?= $totalGuias ?></strong><small>Materiales disponibles</small></article>
          <article class="metric metric-deliveries"><span>Entregas recibidas</span><strong><?= $totalEntregas ?></strong><small>Trabajos enviados</small></article>
          <article class="metric metric-grades"><span>Notas registradas</span><strong><?= $totalNotas ?></strong><small><?= $promedioNotas === null ? "Sin promedio todavía" : "Promedio " . $promedioNotas . " / 5.0" ?></small></article>
        </section>

        <section class="admin-section">
          <div class="admin-section-heading"><div><p class="admin-eyebrow">ACCESO RÁPIDO</p><h2>Gestión académica</h2></div></div>
          <div class="tarjetas admin-cards">
            <a class="card admin-card users-card" href="admin.php?view=usuarios">
              <span class="admin-card-icon"><i data-lucide="users" aria-hidden="true"></i></span>
              <span class="admin-card-copy"><strong>Gestionar usuarios</strong><small>Consulta cuentas, roles y grados.</small></span>
              <i class="admin-card-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
            </a>
            <a class="card admin-card grades-card" href="admin.php?view=notas">
              <span class="admin-card-icon"><i data-lucide="clipboard-list" aria-hidden="true"></i></span>
              <span class="admin-card-copy"><strong>Revisar calificaciones</strong><small>Consulta notas registradas en la plataforma.</small></span>
              <i class="admin-card-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
            </a>
            <a class="card admin-card reports-card" href="admin.php?view=reportes">
              <span class="admin-card-icon"><i data-lucide="chart-no-axes-column-increasing" aria-hidden="true"></i></span>
              <span class="admin-card-copy"><strong>Ver reportes</strong><small>Explora distribución y avance académico.</small></span>
              <i class="admin-card-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
            </a>
          </div>
        </section>
      <?php elseif ($vista === "usuarios"): ?>
        <?php
        $busqueda = trim($_GET["q"] ?? "");
        $filtroRol = $_GET["rol"] ?? "";
        if (!in_array($filtroRol, ["", "estudiante", "docente", "admin"], true)) {
            $filtroRol = "";
        }
        $patronBusqueda = "%" . $busqueda . "%";
        $consultaUsuarios = $conexion->prepare(
            "SELECT id, nombre, apellido, correo, documento, rol, grado, materia, activo
             FROM usuarios
             WHERE (? = '' OR CONCAT_WS(' ', nombre, apellido, correo, documento) LIKE ?)
               AND (? = '' OR rol = ?)
             ORDER BY activo DESC, rol, apellido, nombre"
        );
        $consultaUsuarios->bind_param("ssss", $busqueda, $patronBusqueda, $filtroRol, $filtroRol);
        $consultaUsuarios->execute();
        $usuarios = $consultaUsuarios->get_result();

        $usuarioEditar = null;
        $idEdicion = filter_var($_GET["edit"] ?? null, FILTER_VALIDATE_INT);
        if ($idEdicion) {
            $buscarEdicion = $conexion->prepare(
                "SELECT id, nombre, apellido, correo, documento, rol, grado, materia
                 FROM usuarios WHERE id = ?"
            );
            $buscarEdicion->bind_param("i", $idEdicion);
            $buscarEdicion->execute();
            $usuarioEditar = $buscarEdicion->get_result()->fetch_assoc() ?: null;
            $buscarEdicion->close();
        }
        $esEdicion = is_array($usuarioEditar);
        ?>
        <section class="admin-section">
          <div class="admin-section-heading">
            <div><p class="admin-eyebrow">DIRECTORIO</p><h2>Cuentas registradas</h2></div>
            <a class="admin-action-button" href="admin.php?view=usuarios&new=1"><i data-lucide="user-plus" aria-hidden="true"></i> Crear usuario</a>
          </div>

          <?php if (isset($_GET["new"]) || $esEdicion): ?>
            <form id="admin-user-form" class="admin-user-form" method="post" action="admin.php?view=usuarios">
              <input type="hidden" name="csrf_token" value="<?= adminHtml($csrfToken) ?>">
              <input type="hidden" name="accion" value="<?= $esEdicion ? "actualizar" : "crear" ?>">
              <?php if ($esEdicion): ?><input type="hidden" name="usuario_id" value="<?= (int)$usuarioEditar["id"] ?>"><?php endif; ?>
              <div class="user-form-heading">
                <div><p class="admin-eyebrow"><?= $esEdicion ? "MODIFICAR CUENTA" : "NUEVA CUENTA" ?></p><h3><?= $esEdicion ? "Editar usuario" : "Crear usuario" ?></h3></div>
                <a class="form-close" href="admin.php?view=usuarios" aria-label="Cerrar formulario">×</a>
              </div>
              <div class="user-form-grid">
                <label>Nombre<input name="nombre" type="text" maxlength="80" required value="<?= adminHtml($usuarioEditar["nombre"] ?? "") ?>"></label>
                <label>Apellido<input name="apellido" type="text" maxlength="80" required value="<?= adminHtml($usuarioEditar["apellido"] ?? "") ?>"></label>
                <label>Correo<input name="correo" type="email" maxlength="190" required value="<?= adminHtml($usuarioEditar["correo"] ?? "") ?>"></label>
                <label>Documento<input name="documento" type="text" maxlength="40" required value="<?= adminHtml($usuarioEditar["documento"] ?? "") ?>"></label>
                <label>Rol<select name="rol" required>
                  <?php foreach (["estudiante" => "Estudiante", "docente" => "Docente", "admin" => "Administrador"] as $valorRol => $etiquetaRol): ?>
                    <option value="<?= $valorRol ?>" <?= ($usuarioEditar["rol"] ?? "estudiante") === $valorRol ? "selected" : "" ?>><?= $etiquetaRol ?></option>
                  <?php endforeach; ?>
                </select></label>
                <label id="grado-field">Grado<select name="grado">
                  <option value="">No aplica</option>
                  <?php foreach (["10-1", "10-2", "10-3", "10-4", "10-5", "10-6", "10-7", "11-1", "11-2", "11-3", "11-4", "11-5", "11-6", "11-7"] as $grado): ?>
                    <option value="<?= $grado ?>" <?= ($usuarioEditar["grado"] ?? "") === $grado ? "selected" : "" ?>><?= $grado ?></option>
                  <?php endforeach; ?>
                </select></label>
                <label id="materia-field">Materia<select name="materia">
                  <option value="">No aplica</option>
                  <?php foreach (["Lectura Critica", "Matematicas", "Ciencias Naturales", "Competencias Ciudadanas", "Ingles"] as $materia): ?>
                    <option value="<?= adminHtml($materia) ?>" <?= ($usuarioEditar["materia"] ?? "") === $materia ? "selected" : "" ?>><?= adminHtml($materia) ?></option>
                  <?php endforeach; ?>
                </select></label>
                <label class="password-field">Contraseña<?= $esEdicion ? " (opcional)" : "" ?><input name="password" type="password" minlength="8" <?= $esEdicion ? "" : "required" ?> autocomplete="new-password" placeholder="<?= $esEdicion ? "Dejar vacío para conservar la actual" : "Mínimo 8 caracteres" ?>"></label>
              </div>
              <p class="form-hint">El grado se aplica a estudiantes y la materia a docentes. Para administradores ambos campos quedan vacíos.</p>
              <div class="user-form-actions"><a class="button-secondary" href="admin.php?view=usuarios">Cancelar</a><button class="admin-action-button" type="submit"><?= $esEdicion ? "Guardar cambios" : "Crear cuenta" ?></button></div>
            </form>
          <?php endif; ?>

          <form class="admin-filters" method="get" action="admin.php">
            <input type="hidden" name="view" value="usuarios">
            <label class="search-field"><span class="visually-hidden">Buscar usuarios</span><i data-lucide="search" aria-hidden="true"></i><input name="q" type="search" placeholder="Nombre, correo o documento" value="<?= adminHtml($busqueda) ?>"></label>
            <label class="role-filter"><span class="visually-hidden">Filtrar por rol</span><select name="rol"><option value="">Todos los roles</option><option value="estudiante" <?= $filtroRol === "estudiante" ? "selected" : "" ?>>Estudiante</option><option value="docente" <?= $filtroRol === "docente" ? "selected" : "" ?>>Docente</option><option value="admin" <?= $filtroRol === "admin" ? "selected" : "" ?>>Administrador</option></select></label>
            <button class="filter-button" type="submit">Buscar</button>
            <?php if ($busqueda !== "" || $filtroRol !== ""): ?><a class="clear-filter" href="admin.php?view=usuarios">Limpiar</a><?php endif; ?>
          </form>

          <div class="admin-table-wrap">
            <table class="admin-table admin-users-table">
              <thead><tr><th>Nombre</th><th>Correo / documento</th><th>Rol</th><th>Grado</th><th>Materia</th><th>Estado</th><th>Acciones</th></tr></thead>
              <tbody>
                <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                  <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                    <tr class="<?= (int)$usuario["activo"] === 1 ? "" : "user-inactive" ?>">
                      <td><?= adminHtml(trim(($usuario["nombre"] ?? "") . " " . ($usuario["apellido"] ?? ""))) ?></td>
                      <td><?= adminHtml($usuario["correo"] ?? "") ?><small class="table-secondary"><?= adminHtml($usuario["documento"] ?? "") ?></small></td>
                      <td><span class="role-tag role-<?= adminHtml($usuario["rol"] ?? "") ?>"><?= adminHtml(ucfirst($usuario["rol"] ?? "")) ?></span></td>
                      <td><?= adminHtml($usuario["grado"] ?? "—") ?></td>
                      <td><?= adminHtml($usuario["materia"] ?? "—") ?></td>
                      <td><span class="status-tag <?= (int)$usuario["activo"] === 1 ? "status-active" : "status-inactive" ?>"><?= (int)$usuario["activo"] === 1 ? "Activo" : "Inactivo" ?></span></td>
                      <td><div class="row-actions"><a class="edit-user" href="admin.php?view=usuarios&edit=<?= (int)$usuario["id"] ?>" aria-label="Editar usuario">Editar</a>
                        <?php if ((int)$usuario["id"] !== (int)$_SESSION["id"]): ?>
                          <form method="post" action="admin.php?view=usuarios" onsubmit="return confirm('¿Confirmas el cambio de estado de esta cuenta?')">
                            <input type="hidden" name="csrf_token" value="<?= adminHtml($csrfToken) ?>"><input type="hidden" name="accion" value="alternar_estado"><input type="hidden" name="usuario_id" value="<?= (int)$usuario["id"] ?>">
                            <button class="toggle-user <?= (int)$usuario["activo"] === 1 ? "deactivate-user" : "activate-user" ?>" type="submit"><?= (int)$usuario["activo"] === 1 ? "Desactivar" : "Reactivar" ?></button>
                          </form>
                          <form method="post" action="admin.php?view=usuarios" onsubmit="return confirm('La eliminación es permanente y solo se permite sin historial. ¿Continuar?')">
                            <input type="hidden" name="csrf_token" value="<?= adminHtml($csrfToken) ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="usuario_id" value="<?= (int)$usuario["id"] ?>">
                            <button class="toggle-user delete-user" type="submit">Eliminar</button>
                          </form>
                        <?php endif; ?>
                      </div></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="7" class="table-empty">No hay usuarios que coincidan con la búsqueda.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php elseif ($vista === "notas"): ?>
        <?php
        $notas = $conexion->query(
            "SELECT usuarios.nombre, usuarios.apellido, guias.materia,
                    guias.titulo, entregas.nota, entregas.fecha_entrega AS fecha
             FROM entregas
             INNER JOIN usuarios ON usuarios.id = entregas.estudiante_id
             INNER JOIN guias ON guias.id = entregas.guia_id
             WHERE entregas.nota IS NOT NULL
                  UNION ALL
                  SELECT usuarios.nombre, usuarios.apellido, notas_estudiantes.materia,
                    'Nota directa' AS titulo, notas_estudiantes.nota,
                          NULL AS fecha
                  FROM notas_estudiantes
                  INNER JOIN usuarios ON usuarios.id = notas_estudiantes.estudiante_id
                  ORDER BY fecha DESC"
        );
        ?>
        <section class="admin-section">
          <div class="admin-section-heading"><div><p class="admin-eyebrow">SEGUIMIENTO ACADÉMICO</p><h2>Notas de entregas</h2></div><span class="table-count"><?= $totalNotas ?> notas</span></div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead><tr><th>Estudiante</th><th>Materia</th><th>Actividad</th><th>Nota</th><th>Fecha</th></tr></thead>
              <tbody>
                <?php if ($notas && $notas->num_rows > 0): ?>
                  <?php while ($nota = $notas->fetch_assoc()): ?>
                    <tr>
                      <td><?= adminHtml(trim($nota["nombre"] . " " . $nota["apellido"])) ?></td>
                      <td><?= adminHtml($nota["materia"]) ?></td>
                      <td><?= adminHtml($nota["titulo"]) ?></td>
                      <td><strong class="grade-value"><?= number_format((float)$nota["nota"], 1) ?></strong></td>
                      <td><?= adminHtml($nota["fecha"] ?? "—") ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="5" class="table-empty">Todavía no hay notas de entregas calificadas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php elseif ($vista === "reportes"): ?>
        <?php
        $grados = $conexion->query(
            "SELECT COALESCE(NULLIF(grado, ''), 'Sin grado') AS grado, COUNT(*) AS estudiantes
             FROM usuarios
             WHERE rol = 'estudiante'
             GROUP BY COALESCE(NULLIF(grado, ''), 'Sin grado')
             ORDER BY grado"
        );
        ?>
        <section class="admin-section">
          <div class="admin-section-heading"><div><p class="admin-eyebrow">RESUMEN</p><h2>Distribución de estudiantes</h2></div></div>
          <div class="admin-report-list">
            <?php if ($grados && $grados->num_rows > 0): ?>
              <?php while ($filaGrado = $grados->fetch_assoc()): ?>
                <div class="report-row"><span><?= adminHtml($filaGrado["grado"]) ?></span><strong><?= (int)$filaGrado["estudiantes"] ?></strong></div>
              <?php endwhile; ?>
            <?php else: ?>
              <p class="table-empty">No hay estudiantes registrados para resumir.</p>
            <?php endif; ?>
          </div>
          <div class="report-summary"><span>Guías publicadas</span><strong><?= $totalGuias ?></strong><span>Entregas recibidas</span><strong><?= $totalEntregas ?></strong><span>Calificaciones registradas</span><strong><?= $totalNotas ?></strong></div>
        </section>
      <?php else: ?>
        <section class="admin-section">
          <div class="admin-section-heading"><div><p class="admin-eyebrow">REFERENCIA DEL SISTEMA</p><h2>Parámetros académicos</h2></div></div>
          <div class="settings-grid">
            <article class="settings-item"><span>Escala de calificación</span><strong>0.0 a 5.0</strong></article>
            <article class="settings-item"><span>Grados habilitados</span><strong>10-1 a 10-7 · 11-1 a 11-7</strong></article>
            <article class="settings-item"><span>Materias</span><strong>Lectura Crítica, Matemáticas, Ciencias Naturales, Competencias Ciudadanas e Inglés</strong></article>
          </div>
          <p class="settings-note">Estos parámetros son informativos; el proyecto todavía no cuenta con edición de configuración desde el panel.</p>
        </section>
      <?php endif; ?>
    </main>
  </div>

  <footer class="admin-footer">ACADEMIX <span>·</span> Plataforma educativa</footer>
  <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
  <script>if (window.lucide) window.lucide.createIcons();</script>
  <script>
    const adminRoleField = document.querySelector('#admin-user-form [name="rol"]');
    if (adminRoleField) {
      const gradoField = document.querySelector('#grado-field');
      const materiaField = document.querySelector('#materia-field');
      const gradoSelect = gradoField.querySelector('select');
      const materiaSelect = materiaField.querySelector('select');

      function actualizarCamposPorRol() {
        const esEstudiante = adminRoleField.value === 'estudiante';
        const esDocente = adminRoleField.value === 'docente';
        gradoField.hidden = !esEstudiante;
        materiaField.hidden = !esDocente;
        gradoSelect.required = esEstudiante;
        materiaSelect.required = esDocente;
        gradoSelect.disabled = !esEstudiante;
        materiaSelect.disabled = !esDocente;
        if (!esEstudiante) gradoSelect.value = '';
        if (!esDocente) materiaSelect.value = '';
      }

      adminRoleField.addEventListener('change', actualizarCamposPorRol);
      actualizarCamposPorRol();
    }
  </script>
</body>
</html>
<?php $conexion->close(); ?>