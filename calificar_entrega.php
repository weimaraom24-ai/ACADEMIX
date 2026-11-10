<?php

/*
    Inicia la sesión para poder acceder a las variables
    almacenadas en $_SESSION.
*/
session_start();

/*
    Incluye el archivo de conexión a la base de datos.
*/
include("conexion.php");


/*
    Verifica que exista una sesión iniciada.
    Si no existe un ID de usuario, se detiene la ejecución.
*/
if (!isset($_SESSION["id"])) {
    die("Debes iniciar sesión.");
}


/*
    Verifica que el usuario tenga el rol de docente.
    Solo los docentes tienen permiso para calificar entregas.
*/
if ($_SESSION["rol"] !== "docente") {
    die("No tienes permiso para calificar.");
}


/*
    Verifica que la solicitud haya sido enviada mediante
    el método POST.
*/
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no válido.");
}


/*
    Obtiene los datos enviados desde el formulario.
    entrega_id identifica la entrega que se desea calificar.
    nota contiene la calificación asignada.
    comentario contiene la observación realizada por el docente.
*/
$entrega_id = $_POST["entrega_id"];
$nota = $_POST["nota"];
$comentario = $_POST["comentario"];


/*
    Obtiene el ID del docente que inició sesión.
*/
$docente_id = $_SESSION["id"];


/*
    Verificar que la entrega exista.

    La consulta comprueba que la entrega exista
    y que pertenezca a una guía creada por el docente
    que actualmente está realizando la calificación.
*/
$sql = "SELECT entregas.id
        FROM entregas
        INNER JOIN guias
        ON entregas.guia_id = guias.id
        WHERE entregas.id = ?
        AND guias.docente_id = ?";


/*
    Prepara la consulta SQL para ejecutarla de forma segura.
*/
$stmt = $conexion->prepare($sql);

/*
    Asocia los valores de entrega_id y docente_id
    a los parámetros de la consulta.
*/
$stmt->bind_param("ii", $entrega_id, $docente_id);

/*
    Ejecuta la consulta.
*/
$stmt->execute();

/*
    Obtiene el resultado de la consulta.
*/
$resultado = $stmt->get_result();


/*
    Si no se encuentra ninguna entrega relacionada
    con el docente, se detiene el proceso.
*/
if ($resultado->num_rows === 0) {
    die("Esta entrega no pertenece a tus guías.");
}


/*
    Cierra la consulta anterior.
*/
$stmt->close();


/*
    Verificar que la nota esté entre 0 y 5.
    Si la calificación está fuera de este rango,
    se detiene el proceso.
*/
if ($nota < 0 || $nota > 5) {
    die("La nota debe estar entre 0.0 y 5.0.");
}


/*
    Verificar si ya existe una calificación
    asociada a la entrega.
*/
$sql = "SELECT id FROM calificaciones WHERE entrega_id = ?";


/*
    Prepara la consulta para comprobar
    si la entrega ya tiene una calificación.
*/
$stmt = $conexion->prepare($sql);

/*
    Asocia el ID de la entrega al parámetro de la consulta.
*/
$stmt->bind_param("i", $entrega_id);

/*
    Ejecuta la consulta.
*/
$stmt->execute();

/*
    Obtiene el resultado de la consulta.
*/
$resultado = $stmt->get_result();


/*
    Si ya existe una calificación para la entrega,
    se actualiza la información existente.
*/
if ($resultado->num_rows > 0) {

    /*
        Obtiene los datos de la calificación existente.
    */
    // Actualizar calificación existente
    $calificacion = $resultado->fetch_assoc();

    /*
        Obtiene el ID de la calificación
        que será actualizada.
    */
    $id = $calificacion["id"];

    /*
        Cierra la consulta anterior antes de realizar
        la consulta de actualización.
    */
    $stmt->close();


    /*
        Consulta SQL para actualizar la nota,
        el comentario, el docente y la fecha
        de calificación.
    */
    $sql = "UPDATE calificaciones
            SET nota = ?, comentario = ?, docente_id = ?, fecha_calificacion = NOW()
            WHERE id = ?";


    /*
        Prepara la consulta de actualización.
    */
    $stmt = $conexion->prepare($sql);


    /*
        Asocia los valores correspondientes
        a los parámetros de la consulta.
    */
    $stmt->bind_param(
        "dsii",
        $nota,
        $comentario,
        $docente_id,
        $id
    );

} else {

    /*
        Si no existe una calificación anterior,
        se crea una nueva.
    */
    // Crear nueva calificación
    $stmt->close();


    /*
        Consulta SQL para insertar una nueva
        calificación en la tabla calificaciones.
    */
    $sql = "INSERT INTO calificaciones
            (entrega_id, docente_id, nota, comentario)
            VALUES (?, ?, ?, ?)";


    /*
        Prepara la consulta de inserción.
    */
    $stmt = $conexion->prepare($sql);


    /*
        Asocia los valores enviados a los parámetros
        de la consulta.
    */
    $stmt->bind_param(
        "iids",
        $entrega_id,
        $docente_id,
        $nota,
        $comentario
    );
}


/*
    Ejecuta la consulta de actualización o inserción.
*/
if ($stmt->execute()) {

    /*
        Si la operación fue exitosa, redirige al docente
        hacia la página de notas mostrando un mensaje
        indicando que la calificación fue guardada.
    */
    header("Location: notasd.php?mensaje=calificacion_guardada");

    /*
        Detiene la ejecución del script después
        de realizar la redirección.
    */
    exit();

} else {

    /*
        Si ocurre un error al guardar la información,
        muestra el mensaje de error generado por la consulta.
    */
    echo "Error al guardar la calificación: " . $stmt->error;
}


/*
    Cierra la consulta preparada.
*/
$stmt->close();

/*
    Cierra la conexión con la base de datos.
*/
$conexion->close();

?>