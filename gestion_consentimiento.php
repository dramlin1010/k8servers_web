<?php
session_start();

header('Content-Type: application/json');

$mongo_uri = "mongodb://mongodb-host-svc:27017";
$mongo_db_name = "k8servers_nosql";
$mongo_collection_name = "consentimientos_cookies";

$response = ['status' => 'error', 'message' => 'Acción no reconocida.'];

try {
    $mongo_client = new MongoDB\Client($mongo_uri, [], ['serverSelectionTimeoutMS' => 5000]);
    $db = $mongo_client->$mongo_db_name;
    $collection = $db->$mongo_collection_name;

    $db->command(['ping' => 1]);

} catch (MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    error_log("MongoDB Connection Timeout: " . $e->getMessage() . " | URI: " . $mongo_uri);
    $response['message'] = "No se pudo conectar al servicio de base de datos de consentimientos (timeout).";
    echo json_encode($response);
    exit();
} catch (MongoDB\Driver\Exception\Exception $e) {
    error_log("Error de conexión/operación con MongoDB: " . $e->getMessage() . " | URI: " . $mongo_uri);
    $response['message'] = "Error interno del servidor al interactuar con la base de datos de consentimientos.";
    echo json_encode($response);
    exit();
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage() . " | URI: " . $mongo_uri);
    $response['message'] = "Error interno del servidor.";
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($action === 'guardar_consentimiento') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Método no permitido para esta acción.';
        echo json_encode($response);
        exit();
    }

    $preferencias_raw = $_POST['preferencias'] ?? null;
    $preferencias = is_string($preferencias_raw) ? json_decode($preferencias_raw, true) : $preferencias_raw;

    if (!is_array($preferencias) || !isset($preferencias['necesarias'])) {
        $response['message'] = 'Datos de preferencias no válidos o incompletos.';
        echo json_encode($response);
        exit();
    }

    $cliente_id = $_SESSION['ClienteID'] ?? null;
    $session_id_anonimo = session_id();

    $identificador_tipo = $cliente_id ? "registrado" : "anonimo";
    $identificador_valor = $cliente_id ? (string)$cliente_id : "sess_" . $session_id_anonimo;

    $documento_consentimiento = [
        'identificador_usuario' => [
            'tipo' => $identificador_tipo,
            'valor' => $identificador_valor,
        ],
        'version_politica_cookies' => "1.0",
        'fecha_consentimiento' => new MongoDB\BSON\UTCDateTime(),
        'fecha_ultima_modificacion' => new MongoDB\BSON\UTCDateTime(),
        'fuente_consentimiento' => $_POST['fuente'] ?? 'banner_cookies',
        'ip_direccion_hash' => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip'),
        'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown_ua'),
        'consentimiento_general_aceptado' => isset($preferencias['aceptar_todas']) ? (bool)$preferencias['aceptar_todas'] : true,
        'preferencias_categorias' => [
            'necesarias' => true,
            'analiticas' => isset($preferencias['analiticas']) ? (bool)$preferencias['analiticas'] : false,
            'marketing' => isset($preferencias['marketing']) ? (bool)$preferencias['marketing'] : false,
            'funcionales' => isset($preferencias['funcionales']) ? (bool)$preferencias['funcionales'] : false,
        ],
    ];

    try {
        $updateResult = $collection->updateOne(
            ['identificador_usuario.valor' => $identificador_valor],
            ['$set' => $documento_consentimiento, '$setOnInsert' => ['fecha_consentimiento' => new MongoDB\BSON\UTCDateTime()]],
            ['upsert' => true]
        );

        if ($updateResult->getMatchedCount() > 0 || $updateResult->getUpsertedCount() > 0) {
            $response['status'] = 'success';
            $response['message'] = 'Preferencias de cookies guardadas.';
            // Cookie para indicar que el consentimiento se ha dado (para el banner)
            setcookie("cookie_consent_given", "true", time() + (86400 * 365), "/"); // Expira en 1 año
        } else {
            $response['message'] = 'No se pudieron guardar las preferencias.';
        }
    } catch (Exception $e) {
        error_log("Error al guardar consentimiento en MongoDB: " . $e->getMessage());
        $response['message'] = 'Error interno al guardar preferencias.';
    }
    echo json_encode($response);
    exit();

} elseif ($action === 'obtener_consentimiento') {
    $cliente_id = $_SESSION['ClienteID'] ?? null;
    $session_id_anonimo = session_id();
    $identificador_valor = $cliente_id ? (string)$cliente_id : "sess_" . $session_id_anonimo;

    $consentimiento = $collection->findOne(['identificador_usuario.valor' => $identificador_valor]);

    if ($consentimiento) {
        $response['status'] = 'success';
        $response['message'] = 'Consentimiento encontrado.';
        $consentimiento['fecha_consentimiento'] = $consentimiento['fecha_consentimiento']->toDateTime()->format(DateTime::ATOM);
        $consentimiento['fecha_ultima_modificacion'] = $consentimiento['fecha_ultima_modificacion']->toDateTime()->format(DateTime::ATOM);
        unset($consentimiento['_id']);
        $response['data'] = $consentimiento;
    } else {
        $response['message'] = 'No se encontró consentimiento previo.';
        $response['status'] = 'not_found';
    }
    echo json_encode($response);
    exit();
}

echo json_encode($response);
?>
