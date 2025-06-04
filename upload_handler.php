<?php
require_once 'config.php';
session_start();
require 'conexion.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Error desconocido al iniciar.', 'files' => []];

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
$clienteID_session = $_SESSION['ClienteID'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['message'] = "Método no permitido.";
    echo json_encode($response);
    exit();
}

$sitio_id = isset($_POST['sitio_id_upload']) ? (int)$_POST['sitio_id_upload'] : null;

if (!$sitio_id) {
    $response['message'] = "No se especificó el sitio de destino (sitio_id_upload).";
    echo json_encode($response);
    exit();
}

if (empty($_FILES['archivos_usuario']) || !isset($_FILES['archivos_usuario']['name'])) {
    $response['message'] = "No se recibieron archivos o el formato es incorrecto.";
    echo json_encode($response);
    exit();
}

$stmt_sitio = $conn->prepare("SELECT DirectorioEFSRuta FROM SitioWeb WHERE SitioID = ? AND ClienteID = ? AND EstadoServicio = 'activo' AND EstadoAprovisionamientoK8S = 'k8s_aprovisionado'");
$directorio_base_sitio_www = null;
if ($stmt_sitio) {
    $stmt_sitio->bind_param("ii", $sitio_id, $clienteID_session);
    $stmt_sitio->execute();
    $result_sitio = $stmt_sitio->get_result();
    if ($row_sitio = $result_sitio->fetch_assoc()) {
        if (!empty($row_sitio['DirectorioEFSRuta'])) {
            $directorio_base_sitio_www = rtrim($row_sitio['DirectorioEFSRuta'], '/') . '/www';
        }
    }
    $stmt_sitio->close();
}

if (!$directorio_base_sitio_www) {
    $response['message'] = "No se pudo determinar el directorio de destino para el sitio seleccionado, o el sitio no está completamente aprovisionado.";
    echo json_encode($response);
    exit();
}

if (!is_dir($directorio_base_sitio_www)) {
    if (!@mkdir($directorio_base_sitio_www, 0775, true)) {
        error_log("UPLOAD_HANDLER: Directorio de subida no existe y no pudo ser creado: $directorio_base_sitio_www. SitioID: $sitio_id");
        $response['message'] = "Error de servidor: el directorio de destino no existe y no pudo ser creado. Contacta a soporte (Ref: UPLDIRCREATE_$sitio_id).";
        echo json_encode($response);
        exit();
    } else {
        @chgrp($directorio_base_sitio_www, 101);
        @chmod($directorio_base_sitio_www, 0775);
    }
}

if (!is_writable($directorio_base_sitio_www)) {
    error_log("UPLOAD_HANDLER: Directorio de subida no escribible por el usuario PHP: $directorio_base_sitio_www. SitioID: $sitio_id. Verifica permisos de EFS y fsGroup del pod PHP.");
    $response['message'] = "Error de permisos en el servidor: el directorio de destino no es escribible. Contacta a soporte (Ref: UPLPERM_$sitio_id).";
    echo json_encode($response);
    exit();
}

$archivos_subidos = $_FILES['archivos_usuario'];
$response['status'] = 'success'; 
$response['message'] = 'Proceso de subida completado.';
$algun_archivo_subido = false;

$nombres_archivos = is_array($archivos_subidos['name']) ? $archivos_subidos['name'] : [$archivos_subidos['name']];
$nombres_temporales = is_array($archivos_subidos['tmp_name']) ? $archivos_subidos['tmp_name'] : [$archivos_subidos['tmp_name']];
$errores_archivos = is_array($archivos_subidos['error']) ? $archivos_subidos['error'] : [$archivos_subidos['error']];

for ($i = 0; $i < count($nombres_archivos); $i++) {
    $nombre_original = $nombres_archivos[$i];
    $nombre_temporal = $nombres_temporales[$i];
    $error_archivo = $errores_archivos[$i];
    $file_status = ['name' => htmlspecialchars($nombre_original), 'uploaded' => false, 'message' => ''];

    if ($error_archivo !== UPLOAD_ERR_OK) {
        switch ($error_archivo) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $file_status['message'] = "El archivo es demasiado grande.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $file_status['message'] = "El archivo se subió solo parcialmente.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $file_status['message'] = "No se subió ningún archivo (puede ser un campo vacío).";
                break;
            default:
                $file_status['message'] = "Error desconocido al subir (código: $error_archivo).";
        }
        $response['files'][] = $file_status;
        $response['status'] = 'partial_error';
        continue;
    }
    
    if (empty($nombre_original)) {
        continue;
    }

    $nombre_seguro = preg_replace("/[^a-zA-Z0-9._\-\s]/", "_", basename($nombre_original));
    $nombre_seguro = str_replace(' ', '_', $nombre_seguro);

    if (empty($nombre_seguro) || $nombre_seguro === '.' || $nombre_seguro === '..') {
        $file_status['message'] = "Nombre de archivo inválido después de sanitizar.";
        $response['files'][] = $file_status;
        $response['status'] = 'partial_error';
        continue;
    }
    
    $ruta_destino = $directorio_base_sitio_www . '/' . $nombre_seguro;

    if (file_exists($ruta_destino)) {
        $file_status['message'] = "El archivo ya existe. No se sobrescribió.";
    }

    if (move_uploaded_file($nombre_temporal, $ruta_destino)) {
        @chmod($ruta_destino, 0664); 
        $file_status['uploaded'] = true;
        $file_status['message'] = "Subido correctamente.";
        $algun_archivo_subido = true;
    } else {
        $php_errormsg_move = error_get_last()['message'] ?? 'Error desconocido al mover archivo';
        error_log("UPLOAD_HANDLER: Error al mover archivo subido a $ruta_destino. PHP Error: $php_errormsg_move. Temp: $nombre_temporal");
        $file_status['message'] = "Error al guardar el archivo en el servidor.";
        $response['status'] = 'partial_error';
    }
    $response['files'][] = $file_status;
}

if (empty($response['files'])) {
    $response['message'] = 'No se seleccionaron archivos válidos para subir.';
    $response['status'] = 'error';
} elseif ($response['status'] === 'partial_error' && !$algun_archivo_subido) {
    $response['message'] = 'Ningún archivo pudo ser subido.';
    $response['status'] = 'error';
} elseif ($response['status'] === 'partial_error' && $algun_archivo_subido) {
     $response['message'] = 'Algunos archivos fueron subidos, otros tuvieron problemas.';
}


if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

if ($response['status'] === 'success' || $algun_archivo_subido) {
    $_SESSION['success_message_gestor'] = $response['message'];
} elseif ($response['status'] !== 'success' && !empty($response['message'])) {
    $_SESSION['error_message_gestor'] = $response['message'];
}


echo json_encode($response);
exit();
?>
