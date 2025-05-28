<?php
session_start();

header('Content-Type: application/json');
error_log("gestion_consentimiento.php: Script iniciado.");

$mongo_uri = "mongodb://mongodb-host-svc:27017";
$mongo_db_name = "k8servers_nosql";
$mongo_collection_name = "consentimientos_cookies";

$response = ['status' => 'error', 'message' => 'Acción no reconocida.'];
error_log("gestion_consentimiento.php: URI: " . $mongo_uri . ", DB: " . $mongo_db_name . ", Collection: " . $mongo_collection_name);

try {
    error_log("gestion_consentimiento.php: Intentando conectar a MongoDB...");
    $mongo_client = new MongoDB\Client($mongo_uri, [], [
        'serverSelectionTimeoutMS' => 10000,
        'socketTimeoutMS' => 10000
    ]);
    error_log("gestion_consentimiento.php: Cliente MongoDB creado. Intentando seleccionar DB...");
    $db = $mongo_client->$mongo_db_name;
    error_log("gestion_consentimiento.php: DB seleccionada. Intentando hacer ping a la DB...");
    $db->command(['ping' => 1]);
    error_log("gestion_consentimiento.php: Ping a MongoDB exitoso.");
    $collection = $db->$mongo_collection_name;
    error_log("gestion_consentimiento.php: Colección seleccionada.");

} catch (MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    error_log("gestion_consentimiento.php: MongoDB Connection Timeout Exception: " . $e->getMessage());
    $response['message'] = "No se pudo conectar al servicio de base de datos (timeout).";
    echo json_encode($response);
    exit();
} catch (MongoDB\Driver\Exception\Exception $e) {
    error_log("gestion_consentimiento.php: MongoDB Driver Exception: " . $e->getMessage());
    $response['message'] = "Error interno del servidor al interactuar con la base de datos (driver).";
    echo json_encode($response);
    exit();
} catch (Exception $e) {
    error_log("gestion_consentimiento.php: General Exception durante la conexión: " . $e->getMessage());
    $response['message'] = "Error interno del servidor durante la conexión.";
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;
error_log("gestion_consentimiento.php: Acción recibida: " . $action);

if ($action === 'guardar_consentimiento') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Método no permitido para esta acción.';
        error_log("gestion_consentimiento.php: Método no permitido: " . $_SERVER['REQUEST_METHOD']);
        echo json_encode($response);
        exit();
    }

    $preferencias_raw = $_POST['preferencias'] ?? null;
    $preferencias = is_string($preferencias_raw) ? json_decode($preferencias_raw, true) : $preferencias_raw;
    error_log("gestion_consentimiento.php: Preferencias recibidas (raw): " . $preferencias_raw);

    if (!is_array($preferencias) || !isset($preferencias['necesarias'])) {
        $response['message'] = 'Datos de preferencias no válidos o incompletos.';
        error_log("gestion_consentimiento.php: Datos de preferencias no válidos. Preferencias: " . json_encode($preferencias));
        echo json_encode($response);
        exit();
    }

    $cliente_id = $_SESSION['ClienteID'] ?? null;
    $session_id_anonimo = session_id();

    $identificador_tipo = $cliente_id ? "registrado" : "anonimo";
    $identificador_valor = $cliente_id ? (string)$cliente_id : "sess_" . $session_id_anonimo;
    error_log("gestion_consentimiento.php: Identificador Usuario: Tipo=" . $identificador_tipo . ", Valor=" . $identificador_valor);

    $documento_consentimiento = [
        'identificador_usuario' => [
            'tipo' => $identificador_tipo,
            'valor' => $identificador_valor,
        ],
        'version_politica_cookies' => "1.0",
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
    error_log("gestion_consentimiento.php: Preparando para guardar consentimiento. Documento: " . json_encode($documento_consentimiento));

    try {
        $updateResult = $collection->updateOne(
            ['identificador_usuario.valor' => $identificador_valor],
            ['$set' => $documento_consentimiento, '$setOnInsert' => ['fecha_consentimiento' => new MongoDB\BSON\UTCDateTime()]],
            ['upsert' => true]
        );
        error_log("gestion_consentimiento.php: Resultado de updateOne: Matched=" . $updateResult->getMatchedCount() . ", Upserted=" . $updateResult->getUpsertedCount() . ", Modified=" . $updateResult->getModifiedCount());

        if ($updateResult->getMatchedCount() > 0 || $updateResult->getUpsertedCount() > 0 || $updateResult->getModifiedCount() > 0) {
            $response['status'] = 'success';
            $response['message'] = 'Preferencias de cookies guardadas.';
            setcookie("cookie_consent_given", "true", time() + (86400 * 365), "/");
            error_log("gestion_consentimiento.php: Consentimiento guardado exitosamente para " . $identificador_valor);
        } else {
            $response['message'] = 'No se pudieron guardar las preferencias (sin cambios).';
            error_log("gestion_consentimiento.php: No se guardaron preferencias para " . $identificador_valor . " (sin cambios, matched: " . $updateResult->getMatchedCount() . ", upserted: " . $updateResult->getUpsertedCount() . ")");
        }
    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
        error_log("gestion_consentimiento.php: MongoDB BulkWriteException al guardar: " . $e->getMessage() . " | WriteErrors: " . json_encode($e->getWriteResult()->getWriteErrors()));
        $response['message'] = 'Error interno al guardar preferencias (escritura).';
    } catch (Exception $e) {
        error_log("gestion_consentimiento.php: Excepción al guardar consentimiento en MongoDB: " . $e->getMessage());
        $response['message'] = 'Error interno al guardar preferencias.';
    }
    echo json_encode($response);
    exit();

} elseif ($action === 'obtener_consentimiento') {
    error_log("gestion_consentimiento.php: Iniciando acción obtener_consentimiento.");
    $cliente_id = $_SESSION['ClienteID'] ?? null;
    $session_id_anonimo = session_id();
    $identificador_valor = $cliente_id ? (string)$cliente_id : "sess_" . $session_id_anonimo;
    error_log("gestion_consentimiento.php: Buscando consentimiento para: " . $identificador_valor);

    $consentimiento = $collection->findOne(['identificador_usuario.valor' => $identificador_valor]);

    if ($consentimiento) {
        $response['status'] = 'success';
        $response['message'] = 'Consentimiento encontrado.';
        // Convertir BSON UTCDateTime a string ISO 8601 para JSON
        if (isset($consentimiento['fecha_consentimiento']) && $consentimiento['fecha_consentimiento'] instanceof MongoDB\BSON\UTCDateTime) {
            $consentimiento['fecha_consentimiento'] = $consentimiento['fecha_consentimiento']->toDateTime()->format(DateTimeInterface::ATOM);
        }
        if (isset($consentimiento['fecha_ultima_modificacion']) && $consentimiento['fecha_ultima_modificacion'] instanceof MongoDB\BSON\UTCDateTime) {
            $consentimiento['fecha_ultima_modificacion'] = $consentimiento['fecha_ultima_modificacion']->toDateTime()->format(DateTimeInterface::ATOM);
        }
        unset($consentimiento['_id']); // No enviar el _id de MongoDB al cliente
        $response['data'] = $consentimiento;
        error_log("gestion_consentimiento.php: Consentimiento encontrado para " . $identificador_valor . ". Datos: " . json_encode($consentimiento));
    } else {
        $response['message'] = 'No se encontró consentimiento previo.';
        $response['status'] = 'not_found';
        error_log("gestion_consentimiento.php: No se encontró consentimiento para " . $identificador_valor);
    }
    echo json_encode($response);
    exit();
}

error_log("gestion_consentimiento.php: Acción no reconocida o no manejada. Respuesta final: " . json_encode($response));
echo json_encode($response);
?>
