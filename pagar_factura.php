<?php
session_start();
require 'conexion.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    $_SESSION['error_message'] = "Acceso no autorizado. Por favor, inicia sesión.";
    header("Location: login.php");
    exit();
}
if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy(); setcookie("session_token", "", time() - 3600, "/");
    $_SESSION['error_message'] = "Token de sesión inválido. Por favor, inicia sesión de nuevo.";
    header("Location: login.php");
    exit();
}
$clienteID_session = $_SESSION['ClienteID'];

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['factura_id'])) {
    $_SESSION['error_message'] = "Solicitud no válida.";
    header("Location: facturas.php");
    exit();
}

$facturaID = filter_var($_POST['factura_id'], FILTER_VALIDATE_INT);

if ($facturaID === false || $facturaID <= 0) {
    $_SESSION['error_message'] = "ID de factura no válido.";
    header("Location: facturas.php");
    exit();
}

$conn->begin_transaction();

try {
    $sql_info_factura = "SELECT Estado, Monto, Descripcion, SitioID FROM Factura WHERE FacturaID = ? AND ClienteID = ?";
    error_log("PAGAR_FACTURA SQL (info_factura): " . $sql_info_factura . " | Params: FacturaID=$facturaID, ClienteID=$clienteID_session");
    $stmt_info = $conn->prepare($sql_info_factura);
    if (!$stmt_info) throw new Exception("Error al preparar consulta de información de factura: " . $conn->error . " | SQL: " . $sql_info_factura);
    $stmt_info->bind_param("ii", $facturaID, $clienteID_session);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    $factura_info = $result_info->fetch_assoc();
    $stmt_info->close();

    if (!$factura_info) {
        throw new Exception("Factura no encontrada o no te pertenece.");
    }

    if (strtolower($factura_info['Estado']) !== 'pendiente') {
        if (strtolower($factura_info['Estado']) === 'pagado') {
            $_SESSION['info_message'] = "Esta factura ya ha sido pagada.";
        } else {
            $_SESSION['error_message'] = "Esta factura no está pendiente de pago (Estado: " . htmlspecialchars($factura_info['Estado']) . ").";
        }
        $conn->rollback();
        header("Location: facturas.php?highlight_factura=" . $facturaID);
        exit();
    }

    $pago_exitoso = true; 
    $metodo_pago_simulado = "Simulación Pasarela Online";
    $transaccion_id_simulada = "SIM_" . strtoupper(uniqid());
    $mensaje_adicional_exito = "";

    if ($pago_exitoso) {
        $nuevo_estado_factura = 'pagado';
        $fecha_pago = date('Y-m-d H:i:s');

        $sql_update_factura = "UPDATE Factura SET Estado = ?, FechaPago = ?, MetodoPago = ?, TransaccionID = ? WHERE FacturaID = ? AND ClienteID = ?";
        error_log("PAGAR_FACTURA SQL (update_factura): " . $sql_update_factura . " | Params: Estado=$nuevo_estado_factura, FechaPago=$fecha_pago, MetodoPago=$metodo_pago_simulado, TransaccionID=$transaccion_id_simulada, FacturaID=$facturaID, ClienteID=$clienteID_session");
        $stmt_update_factura = $conn->prepare($sql_update_factura);
        if (!$stmt_update_factura) throw new Exception("Error al preparar actualización de factura: " . $conn->error . " | SQL: " . $sql_update_factura);
        $stmt_update_factura->bind_param("ssssii", $nuevo_estado_factura, $fecha_pago, $metodo_pago_simulado, $transaccion_id_simulada, $facturaID, $clienteID_session);
        
        if (!$stmt_update_factura->execute()) {
            throw new Exception("Error al ejecutar actualización de factura: " . $stmt_update_factura->error);
        }
        $stmt_update_factura->close();
        
        $sitioID_asociado = $factura_info['SitioID'];
        $es_factura_activacion = ($sitioID_asociado !== null && stripos($factura_info['Descripcion'], 'activación') !== false);

        if ($es_factura_activacion) {
            $sql_select_sitio = "SELECT EstadoServicio, EstadoAprovisionamientoK8S FROM SitioWeb WHERE SitioID = ? AND ClienteID = ?";
            error_log("PAGAR_FACTURA SQL (select_sitio_for_activation): " . $sql_select_sitio . " | Params: SitioID=$sitioID_asociado, ClienteID=$clienteID_session");
            $stmt_sitio_check = $conn->prepare($sql_select_sitio);
            if (!$stmt_sitio_check) throw new Exception("Error al preparar consulta de verificación de sitio: " . $conn->error . " | SQL: " . $sql_select_sitio);
            $stmt_sitio_check->bind_param("ii", $sitioID_asociado, $clienteID_session);
            $stmt_sitio_check->execute();
            $result_sitio_check = $stmt_sitio_check->get_result();
            $sitio_check_data = $result_sitio_check->fetch_assoc();
            $stmt_sitio_check->close();

            if ($sitio_check_data && $sitio_check_data['EstadoServicio'] === 'pendiente_pago') {
                $nuevo_estado_sitio_servicio = 'activo';
                $nuevo_estado_aprovisionamiento_k8s = 'creacion_directorio_pendiente';

                $sql_update_sitio = "UPDATE SitioWeb SET EstadoServicio = ?, EstadoAprovisionamientoK8S = ? WHERE SitioID = ? AND ClienteID = ?";
                error_log("PAGAR_FACTURA SQL (update_sitio_for_activation): " . $sql_update_sitio . " | Params: EstadoServicio=$nuevo_estado_sitio_servicio, EstadoAprovisionamientoK8S=$nuevo_estado_aprovisionamiento_k8s, SitioID=$sitioID_asociado, ClienteID=$clienteID_session");
                $stmt_update_sitio = $conn->prepare($sql_update_sitio);
                if (!$stmt_update_sitio) throw new Exception("Error al preparar actualización de sitio para activación: " . $conn->error . " | SQL: " . $sql_update_sitio);
                $stmt_update_sitio->bind_param("ssii", $nuevo_estado_sitio_servicio, $nuevo_estado_aprovisionamiento_k8s, $sitioID_asociado, $clienteID_session);
                if (!$stmt_update_sitio->execute()) {
                    throw new Exception("Error al ejecutar actualización de sitio para activación: " . $stmt_update_sitio->error);
                }
                $stmt_update_sitio->close();
                $mensaje_adicional_exito .= " Servicio marcado para activación y preparación de directorio.";

                $sql_insert_tarea = "INSERT INTO Tareas_Aprovisionamiento_K8S (SitioID, TipoTarea, EstadoTarea) VALUES (?, 'aprovisionar_directorio_y_pod', 'pendiente')
                                     ON DUPLICATE KEY UPDATE 
                                        EstadoTarea = VALUES(EstadoTarea), 
                                        Intentos = 0, 
                                        UltimoError = NULL, 
                                        FechaActualizacion = NOW(),
                                        TipoTarea = VALUES(TipoTarea)";
                error_log("PAGAR_FACTURA SQL (insert_tarea_activation): " . $sql_insert_tarea . " | Params: SitioID=$sitioID_asociado");
                $stmt_tarea = $conn->prepare($sql_insert_tarea);
                if (!$stmt_tarea) throw new Exception("Error al preparar tarea de aprovisionamiento para activación: " . $conn->error . " | SQL: " . $sql_insert_tarea);
                $stmt_tarea->bind_param("i", $sitioID_asociado);
                if (!$stmt_tarea->execute()) {
                    error_log("PAGAR_FACTURA: Error al ejecutar creación/actualización de tarea K8S para activación, SitioID $sitioID_asociado: " . $stmt_tarea->error);
                    if (!isset($_SESSION['warning_message'])) $_SESSION['warning_message'] = "";
                    $_SESSION['warning_message'] .= " Hubo un problema al registrar la tarea de aprovisionamiento automático (Ref: K8SACTTASK_$sitioID_asociado).";
                } else {
                    $mensaje_adicional_exito .= " Tarea de aprovisionamiento completo registrada.";
                }
                $stmt_tarea->close();
            } elseif ($sitio_check_data && $sitio_check_data['EstadoServicio'] !== 'pendiente_pago') {
                $mensaje_adicional_exito .= " Pago de factura recurrente procesado (servicio ya activo).";
            }
        }

        $conn->commit();
        $_SESSION['success_message'] = "¡Factura #" . $facturaID . " pagada exitosamente!" . $mensaje_adicional_exito;
        header("Location: facturas.php?highlight_factura=" . $facturaID);
        exit();

    } else {
        $conn->rollback();
        $_SESSION['error_message'] = "El proceso de pago simulado no pudo completarse. Inténtalo de nuevo.";
        header("Location: facturas.php?highlight_factura=" . $facturaID);
        exit();
    }

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        $conn->rollback();
    }
    error_log("PAGAR_FACTURA Exception: FacturaID $facturaID, ClienteID $clienteID_session - " . $e->getMessage());
    $_SESSION['error_message'] = "Ocurrió un error crítico al procesar el pago: " . $e->getMessage();
    header("Location: facturas.php");
    exit();
} finally {
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        $conn->close();
    }
}
?>
