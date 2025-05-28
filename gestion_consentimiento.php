<?php
session_start();

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/dev/stderr');
error_reporting(E_ALL);

header('Content-Type: application/json');
error_log("gestion_consentimiento.php: Script iniciado. error_reporting=" . error_reporting() . ", display_errors=" . ini_get('display_errors') . ", log_errors=" . ini_get('log_errors'));

$mongo_uri = "mongodb://mongodb-host-svc:27017";

$mongo_db_name = "k8servers_nosql";
$mongo_collection_name = "consentimientos_cookies";

$response = ['status' => 'error', 'message' => 'Acción no reconocida (inicial).'];
error_log("gestion_consentimiento.php: URI: " . $mongo_uri . ", DB: " . $mongo_db_name . ", Collection: " . $mongo_collection_name);

try {
    error_log("gestion_consentimiento.php: Intentando crear cliente MongoDB...");
    $mongo_client_options = [
        'serverSelectionTimeoutMS' => 10000,
        'socketTimeoutMS' => 10000,
        'connectTimeoutMS' => 5000
    ];
    error_log("gestion_consentimiento.php: Opciones del cliente MongoDB: " . json_encode($mongo_client_options));

    $mongo_client = new MongoDB\Client($mongo_uri, [], $mongo_client_options);
    error_log("gestion_consentimiento.php: Cliente MongoDB creado OK. Intentando seleccionar DB...");

    $db = $mongo_client->$mongo_db_name;
    error_log("gestion_consentimiento.php: DB seleccionada OK ('" . $mongo_db_name . "'). Intentando hacer ping a la DB...");

    $db->command(['ping' => 1]);
    error_log("gestion_consentimiento.php: Ping a MongoDB (" . $mongo_db_name . ") exitoso.");

    $collection = $db->$mongo_collection_name;
    error_log("gestion_consentimiento.php: Colección seleccionada OK ('" . $mongo_collection_name . "').");

} catch (MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    $error_id = uniqid('mgo_timeout_');
    error_log("gestion_consentimiento.php: [$error_id] CAPTURADA MongoDB ConnectionTimeoutException: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    $response['message'] = "Timeout al conectar con la base de datos de consentimientos. Por favor, reintente. (Ref: $error_id)";
    echo json_encode($response);
    exit();
} catch (MongoDB\Driver\Exception\AuthenticationException $e) {
    $error_id = uniqid('mgo_auth_');
    error_log("gestion_consentimiento.php: [$error_id] CAPTURADA MongoDB AuthenticationException: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    $response['message'] = "Error de autenticación con la base de datos de consentimientos. (Ref: $error_id)";
    echo json_encode($response);
    exit();
} catch (MongoDB\Driver\Exception\RuntimeException $e) {
    $error_id = uniqid('mgo_runtime_');
    error_log("gestion_consentimiento.php: [$error_id] CAPTURADA MongoDB RuntimeException: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    $response['message'] = "Error de ejecución del driver MongoDB. (Ref: $error_id)";
    echo json_encode($response);
    exit();
} catch (MongoDB\Driver\Exception\Exception $e) {
    $error_id = uniqid('mgo_driver_');
    error_log("gestion_consentimiento.php: [$error_id] CAPTURADA MongoDB Driver Exception (genérica): " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    $response['message'] = "Error del driver MongoDB. (Ref: $error_id)";
    echo json_encode($response);
    exit();
} catch (Throwable $e) {
    $error_id = uniqid('php_throwable_');
    error_log("gestion_consentimiento.php: [$error_id] CAPTURADA Throwable (Error o Exception Genérica): " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    $response['message'] = "Error interno general del servidor. (Ref: $error_id)";
    echo json_encode($response);
    exit();
}

error_log("gestion_consentimiento.php: Conexión a MongoDB y ping exitosos. Procesando acción...");

$action = $_POST['action'] ?? $_GET['action'] ?? null;
error_log("gestion_consentimiento.php: Acción recibida: " . ($action ?? 'ninguna'));

if ($action === 'guardar_consentimiento') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Método no permitido para esta acción.';
        error_log("gestion_consentimiento.php: Método no permitido para guardar_consentimiento: " . $_SERVER['REQUEST_METHOD']);
        echo json_encode($response);
        exit();
    }

    $preferencias_raw = $_POST['preferencias'] ?? null;
    $preferencias = is_string($preferencias_raw) ? json_decode($preferencias_raw, true) : $preferencias_raw;
    error_log("gestion_consentimiento.php: Guardar Consentimiento - Preferencias recibidas (raw): " . ($preferencias_raw ?? 'ninguna'));
    error_log("gestion_consentimiento.php: Guardar Consentimiento - Preferencias decodificadas: " . json_encode($preferencias));


    if (!is_array($preferencias) || !isset($preferencias['necesarias'])) {
        $response['message'] = 'Datos de preferencias no válidos o incompletos.';
        error_log("gestion_consentimiento.php: Guardar Consentimiento - Datos de preferencias no válidos.");
        echo json_encode($response);
        exit();
    }

    $cliente_id = $_SESSION['ClienteID'] ?? null;
    $session_id_anonimo = session_id();

    $identificador_tipo = $cliente_id ? "registrado" : "anonimo";
    $identificador_valor = $cliente_id ? (string)$cliente_id : "sess_" . $session_id_anonimo;
    error_log("gestion_consentimiento.php: Guardar Consentimiento - Identificador Usuario: Tipo=" . $identificador_tipo . ", Valor=" . $identificador_valor);

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
            'necesarias' => true, // Siempre true
            'analiticas' => isset($preferencias['analiticas']) ? (bool)$preferencias['analiticas'] : false,
            'marketing' => isset($preferencias['marketing']) ? (bool)$preferencias['marketing'] : false,
            'funcionales' => isset($preferencias['funcionales']) ? (bool)$preferencias['funcionales'] : false,
        ],
    ];
    error_log("gestion_consentimiento.php: Guardar Consentimiento - Documento a guardar: " . json_encode($documento_consentimiento));

    try {
        $updateResult = $collection->updateOne(
            ['identificador_usuario.valor' => $identificador_valor],
            ['$set' => $documento_consentimiento, '$setOnInsert' => ['fecha_consentimiento' => new MongoDB\BSON\UTCDateTime()]],
            ['upsert' => true]
        );
        error_log("gestion_consentimiento.php: Guardar Consentimiento - Resultado de updateOne: Matched=" . $updateResult->getMatchedCount() . ", Upserted=" . $updateResult->getUpsertedCount() . ", Modified=" . $updateResult->getModifiedCount());

        if ($updateResult->getUpsertedCount() > 0 || $updateResult->getMatchedCount() > 0) {
            $response['status'] = 'success';
            $response['message'] = 'Preferencias de cookies guardadas.';
            setcookie("cookie_consent_given", "true", time() + (86400 * 365), "/"); // Cookie de 1 año
            error_log("gestion_consentimiento.php: Guardar Consentimiento - Éxito para " . $identificador_valor);
        } else {
            // Esto podría ocurrir si el documento existe pero no se modifica, y no es un upsert nuevo.
            $response['message'] = 'No se realizaron cambios en las preferencias (ya estaban así o error).';
            error_log("gestion_consentimiento.php: Guardar Consentimiento - Sin cambios o error para " . $identificador_valor . " (matched: " . $updateResult->getMatchedCount() . ", upserted: " . $updateResult->getUpsertedCount() . ", modified: " . $updateResult->getModifiedCount() . ")");
        }
    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
        $error_id = uniqid('mgo_bulkwrite_');
        error_log("gestion_consentimiento.php: [$error_id] Guardar Consentimiento - MongoDB BulkWriteException: " . $e->getMessage() . " | WriteErrors: " . json_encode($e->getWriteResult()->getWriteErrors()));
        $response['message'] = "Error interno al guardar preferencias (escritura). (Ref: $error_id)";
    } catch (Exception $e) { // Captura otras excepciones durante la operación de guardado
        $error_id = uniqid('mgo_save_');
        error_log("gestion_consentimiento.php: [$error_id] Guardar Consentimiento - Excepción: " . $e->getMessage());
        $response['message'] = "Error interno al procesar el guardado de preferencias. (Ref: $error_id)";
    }
    echo json_encode($response);
    exit();

} elseif ($action === 'obtener_consentimiento') {
    error_log("gestion_consentimiento.php: Acción Obtener Consentimiento - Iniciando.");
    $cliente_id = $_SESSION['ClienteID'] ?? null;
    $session_id_anonimo = session_id();
    $identificador_valor = $cliente_id ? (string)$cliente_id : "sess_" . $session_id_anonimo;
    error_log("gestion_consentimiento.php: Acción Obtener Consentimiento - Buscando para: " . $identificador_valor);

    $consentimiento = $collection->findOne(['identificador_usuario.valor' => $identificador_valor]);

    if ($consentimiento) {
        $response['status'] = 'success';
        $response['message'] = 'Consentimiento encontrado.';
        
        // Convertir fechas BSON a string ISO 8601 para JSON
        if (isset($consentimiento['fecha_consentimiento']) && $consentimiento['fecha_consentimiento'] instanceof MongoDB\BSON\UTCDateTime) {
            $consentimiento['fecha_consentimiento'] = $consentimiento['fecha_consentimiento']->toDateTime()->format(DateTimeInterface::ATOM);
        }
        if (isset($consentimiento['fecha_ultima_modificacion']) && $consentimiento['fecha_ultima_modificacion'] instanceof MongoDB\BSON\UTCDateTime) {
            $consentimiento['fecha_ultima_modificacion'] = $consentimiento['fecha_ultima_modificacion']->toDateTime()->format(DateTimeInterface::ATOM);
        }
        
        unset($consentimiento['_id']); // No enviar el _id de MongoDB al cliente
        $response['data'] = $consentimiento;
        error_log("gestion_consentimiento.php: Acción Obtener Consentimiento - Encontrado para " . $identificador_valor);
    } else {
        $response['message'] = 'No se encontró consentimiento previo.';
        $response['status'] = 'not_found';
        error_log("gestion_consentimiento.php: Acción Obtener Consentimiento - No encontrado para " . $identificador_valor);
    }
    echo json_encode($response);
    exit();
}

error_log("gestion_consentimiento.php: Acción no reconocida o no manejada ('" . ($action ?? 'ninguna') . "'). Respuesta final: " . json_encode($response));
echo json_encode($response);
?>
