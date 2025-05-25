<?php
session_start();
require 'conexion.php';

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
$efs_base_path_clientes = "/mnt/efs-clientes";

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
    $stmt_info = $conn->prepare($sql_info_factura);
    if (!$stmt_info) throw new Exception("Error al preparar consulta de factura: " . $conn->error);
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
        $stmt_update_factura = $conn->prepare($sql_update_factura);
        if (!$stmt_update_factura) throw new Exception("Error al preparar actualización de factura: " . $conn->error);
        $stmt_update_factura->bind_param("ssssii", $nuevo_estado_factura, $fecha_pago, $metodo_pago_simulado, $transaccion_id_simulada, $facturaID, $clienteID_session);
        
        if (!$stmt_update_factura->execute()) {
            throw new Exception("Error al actualizar el estado de la factura: " . $stmt_update_factura->error);
        }
        $stmt_update_factura->close();
        
        $sitioID_asociado = $factura_info['SitioID'];
        $es_factura_activacion = ($sitioID_asociado !== null && stripos($factura_info['Descripcion'], 'activación') !== false);

        if ($es_factura_activacion) {
            $stmt_sitio = $conn->prepare("SELECT ClienteID, SubdominioElegido, EstadoServicio, EstadoAprovisionamientoK8S, DirectorioEFSRuta FROM SitioWeb WHERE SitioID = ? AND ClienteID = ?");
            if (!$stmt_sitio) throw new Exception("Error al preparar consulta de sitio: " . $conn->error);
            $stmt_sitio->bind_param("ii", $sitioID_asociado, $clienteID_session);
            $stmt_sitio->execute();
            $result_sitio = $stmt_sitio->get_result();
            $sitio = $result_sitio->fetch_assoc();
            $stmt_sitio->close();

            if ($sitio && $sitio['EstadoServicio'] === 'pendiente_pago') {
                $subdominioCliente = $sitio['SubdominioElegido'];
                $directorioClienteRelativo = preg_replace('/[^a-z0-9\-]/', '', strtolower($subdominioCliente));
                if (empty($directorioClienteRelativo)) $directorioClienteRelativo = "sitio-" . $sitioID_asociado;

                $directorioClienteAbsoluto = rtrim($efs_base_path_clientes, '/') . '/' . $directorioClienteRelativo;
                $directorioWwwAbsoluto = $directorioClienteAbsoluto . '/www';

                $estadoAprovisionamientoActualSitio = $sitio['EstadoAprovisionamientoK8S'];
                $directorioEFSRutaParaGuardar = $directorioClienteAbsoluto;
                $directorioCreadoExitosamente = false;

                if ($estadoAprovisionamientoActualSitio === 'no_iniciado' || $estadoAprovisionamientoActualSitio === 'error_directorio') {
                    if (!is_dir($directorioWwwAbsoluto)) {
                        if (@mkdir($directorioWwwAbsoluto, 0775, true)) {
                            @chgrp($directorioClienteAbsoluto, 101); 
                            @chgrp($directorioWwwAbsoluto, 101);
                            @chmod($directorioClienteAbsoluto, 0775);
                            @chmod($directorioWwwAbsoluto, 0775);
                            $directorioCreadoExitosamente = true;
                            $estadoAprovisionamientoActualSitio = 'directorio_creado';
                            $mensaje_adicional_exito .= " Directorio del servicio preparado.";
                        } else {
                            $estadoAprovisionamientoActualSitio = 'error_directorio';
                            $php_errormsg = error_get_last()['message'] ?? 'Error desconocido al crear directorio';
                            error_log("PAGAR_FACTURA: Error al crear directorio EFS para SitioID $sitioID_asociado: $directorioWwwAbsoluto. PHP Error: $php_errormsg.");
                            $_SESSION['warning_message'] = "Factura pagada, pero hubo un problema al crear el directorio del servicio. Contacta a soporte (Ref: EFSDIR_$sitioID_asociado).";
                        }
                    } else {
                        $directorioCreadoExitosamente = true;
                        $estadoAprovisionamientoActualSitio = 'directorio_creado';
                        $mensaje_adicional_exito .= " Directorio del servicio ya existente y verificado.";
                    }
                } elseif ($estadoAprovisionamientoActualSitio === 'directorio_creado' || $estadoAprovisionamientoActualSitio === 'k8s_manifiesto_pendiente' || $estadoAprovisionamientoActualSitio === 'k8s_aprovisionado') {
                    $directorioCreadoExitosamente = true;
                }

                $nuevo_estado_sitio = 'activo';
                $sql_update_sitio = "UPDATE SitioWeb SET EstadoServicio = ?, EstadoAprovisionamientoK8S = ?, DirectorioEFSRuta = ? WHERE SitioID = ? AND ClienteID = ?";
                $stmt_update_sitio = $conn->prepare($sql_update_sitio);
                if (!$stmt_update_sitio) throw new Exception("Error al preparar actualización de sitio: " . $conn->error);
                $stmt_update_sitio->bind_param("sssii", $nuevo_estado_sitio, $estadoAprovisionamientoActualSitio, $directorioEFSRutaParaGuardar, $sitioID_asociado, $clienteID_session);
                if (!$stmt_update_sitio->execute()) {
                    throw new Exception("Error al actualizar el estado del sitio web: " . $stmt_update_sitio->error);
                }
                $stmt_update_sitio->close();

                if ($directorioCreadoExitosamente && 
                    $estadoAprovisionamientoActualSitio !== 'k8s_aprovisionado' && 
                    $estadoAprovisionamientoActualSitio !== 'error_k8s' &&
                    $estadoAprovisionamientoActualSitio !== 'procesando_k8s' &&
                    $estadoAprovisionamientoActualSitio !== 'k8s_manifiesto_pendiente'
                    ) {
                    
                    $sql_insert_tarea = "INSERT INTO Tareas_Aprovisionamiento_K8S (SitioID, TipoTarea, EstadoTarea) VALUES (?, 'aprovisionar_pod', 'pendiente')
                                         ON DUPLICATE KEY UPDATE 
                                            EstadoTarea = IF(EstadoTarea LIKE 'error_usuario_sftp' OR EstadoTarea LIKE 'error_directorio', VALUES(EstadoTarea), EstadoTarea), 
                                            Intentos = IF(EstadoTarea LIKE 'error_usuario_sftp' OR EstadoTarea LIKE 'error_directorio', 0, Intentos + 1), 
                                            UltimoError = IF(EstadoTarea LIKE 'error_usuario_sftp' OR EstadoTarea LIKE 'error_directorio', NULL, UltimoError), 
                                            FechaActualizacion = NOW(),
                                            TipoTarea = VALUES(TipoTarea)";
                    $stmt_tarea = $conn->prepare($sql_insert_tarea);
                    if (!$stmt_tarea) throw new Exception("Error al preparar tarea de aprovisionamiento: " . $conn->error);
                    $stmt_tarea->bind_param("i", $sitioID_asociado);
                    if (!$stmt_tarea->execute()) {
                        error_log("PAGAR_FACTURA: Error al crear/actualizar tarea K8S para SitioID $sitioID_asociado: " . $stmt_tarea->error);
                        if (!isset($_SESSION['warning_message'])) $_SESSION['warning_message'] = "";
                         $_SESSION['warning_message'] .= " Hubo un problema al registrar la tarea de aprovisionamiento automático (Ref: K8STASK_$sitioID_asociado).";
                    } else {
                        $mensaje_adicional_exito .= " Tarea de aprovisionamiento automático registrada/actualizada.";
                    }
                    $stmt_tarea->close();
                }
            } elseif ($sitio && $sitio['EstadoServicio'] !== 'pendiente_pago') {
                $mensaje_adicional_exito .= " Pago de factura recurrente procesado.";
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
