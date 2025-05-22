<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Error desconocido.', 'files' => []];

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    $response['message'] = "Acceso no autorizado. Debes iniciar sesión.";
    echo json_encode($response);
    exit();
}
if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    $response['message'] = "Token de sesión inválido. Por favor, inicia sesión de nuevo.";
    echo json_encode($response);
    exit();
}
$clienteID = $_SESSION['ClienteID'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['message'] = "Método no permitido.";
    echo json_encode($response);
    exit();
}

$sitio_id = isset($_POST['sitio_id']) ? (int)$_POST['sitio_id'] : null;

if (!$sitio_id) {
    $response['message'] = "No se especificó el sitio de destino.";
    echo json_encode($response);
    exit();
}

if (empty($_FILES['archivos_usuario'])) {
    $response['message'] = "No se recibieron archivos.";
    echo json_encode($response);
    exit();
}

$stmt_sitio = $conn->prepare("SELECT DirectorioEFSRuta FROM SitioWeb WHERE SitioID = ? AND ClienteID = ? AND EstadoServicio = 'activo' AND EstadoAprovisionamientoK8S = 'k8s_aprovisionado'");
$directorio_base_sitio = null;
if ($stmt_sitio) {
    $stmt_sitio->bind_param("ii", $sitio_id, $clienteID);
    $stmt_sitio->execute();
    $result_sitio = $stmt_sitio->get_result();
    if ($row_sitio = $result_sitio->fetch_assoc()) {
        if (!empty($row_sitio['DirectorioEFSRuta'])) {
            $directorio_base_sitio = rtrim($row_sitio['DirectorioEFSRuta'], '/') . '/www';
        }
    }
    $stmt_sitio->close();
}

if (!$directorio_base_sitio) {
    $response['message'] = "No se pudo determinar el directorio de destino para el sitio seleccionado o el sitio no está listo.";
    echo json_encode($response);
    exit();
}

if (!is_dir($directorio_base_sitio) || !is_writable($directorio_base_sitio)) {
    error_log("Directorio de subida no escribible para el usuario PHP: $directorio_base_sitio. SitioID: $sitio_id");
    $response['message'] = "Error de permisos en el servidor: el directorio de destino no es escribible. Contacta a soporte (Ref: UPLPERM_$sitio_id).";
    echo json_encode($response);
    exit();
}

$archivos_subidos = $_FILES['archivos_usuario'];
$response['status'] = 'success';
$response['message'] = 'Proceso de subida completado.';

for ($i = 0; $i < count($archivos_subidos['name']); $i++) {
    $nombre_original = $archivos_subidos['name'][$i];
    $nombre_temporal = $archivos_subidos['tmp_name'][$i];
    $error_archivo = $archivos_subidos['error'][$i];
    $file_status = ['name' => $nombre_original, 'uploaded' => false, 'message' => ''];

    if ($error_archivo !== UPLOAD_ERR_OK) {
        $file_status['message'] = "Error al subir el archivo (código: $error_archivo).";
        $response['files'][] = $file_status;
        $response['status'] = 'partial_error';
        continue;
    }

    // Sanitizar nombre de archivo
    $nombre_seguro = preg_replace("/[^a-zA-Z0-9._\-]/", "_", basename($nombre_original));
    if (empty($nombre_seguro) || $nombre_seguro === '.' || $nombre_seguro === '..') {
        $file_status['message'] = "Nombre de archivo inválido después de sanitizar.";
        $response['files'][] = $file_status;
        $response['status'] = 'partial_error';
        continue;
    }
    
    $ruta_destino = $directorio_base_sitio . '/' . $nombre_seguro;

    if (file_exists($ruta_destino)) {
        $file_status['message'] = "El archivo ya existe en el servidor. No se sobrescribió.";
        $response['files'][] = $file_status;
        $response['status'] = 'partial_error';
        continue;
    }

    if (move_uploaded_file($nombre_temporal, $ruta_destino)) {
        @chmod($ruta_destino, 0664); // rw-rw-r-- para que el grupo (ej. GID 101) pueda modificar si es necesario
        $file_status['uploaded'] = true;
        $file_status['message'] = "Subido correctamente.";
    } else {
        $php_errormsg_move = error_get_last()['message'] ?? 'Error desconocido al mover archivo';
        error_log("Error al mover archivo subido a $ruta_destino. PHP Error: $php_errormsg_move");
        $file_status['message'] = "Error al guardar el archivo en el servidor.";
        $response['status'] = 'partial_error';
    }
    $response['files'][] = $file_status;
}

if ($response['status'] === 'partial_error' && count(array_filter($response['files'], fn($f) => $f['uploaded'])) === 0) {
    $response['message'] = 'Ningún archivo pudo ser subido.';
    $response['status'] = 'error'; // Si nada se subió, es un error total.
} elseif ($response['status'] === 'partial_error') {
     $response['message'] = 'Algunos archivos no pudieron ser subidos.';
}


if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

echo json_encode($response);
exit();
?>
