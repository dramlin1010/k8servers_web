<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    $_SESSION['error_message'] = "Acceso no autorizado.";
    header("Location: login.php");
    exit();
}

if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy();
    setcookie("session_token", "", time() - 3600, "/");
    $_SESSION['error_message'] = "Token de sesión inválido.";
    header("Location: login.php");
    exit();
}

$clienteID = $_SESSION['ClienteID'];

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

try {
    $conn->begin_transaction();

    $sql_info_factura = "SELECT Estado, Monto, Descripcion, SitioID FROM Factura WHERE FacturaID = ? AND ClienteID = ?";
    $stmt_info = $conn->prepare($sql_info_factura);
    $stmt_info->bind_param("ii", $facturaID, $clienteID);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    $factura_info = $result_info->fetch_assoc();
    $stmt_info->close();

    if (!$factura_info) {
        $_SESSION['error_message'] = "Factura no encontrada o no te pertenece.";
        $conn->rollback();
        header("Location: facturas.php");
        exit();
    }

    if (strtolower($factura_info['Estado']) !== 'pendiente') {
        $_SESSION['error_message'] = "Esta factura no está pendiente de pago (Estado: " . htmlspecialchars($factura_info['Estado']) . ").";
        $conn->rollback();
        header("Location: facturas.php?highlight_factura=" . $facturaID);
        exit();
    }

    $pago_exitoso = true; 
    $metodo_pago_simulado = "Simulación Pasarela";
    $transaccion_id_simulada = "SIM_" . strtoupper(uniqid());

    if ($pago_exitoso) {
        $nuevo_estado_factura = 'pagado';
        $fecha_pago = date('Y-m-d H:i:s');

        $sql_update_factura = "UPDATE Factura SET Estado = ?, FechaPago = ?, MetodoPago = ?, TransaccionID = ? WHERE FacturaID = ? AND ClienteID = ?";
        $stmt_update_factura = $conn->prepare($sql_update_factura);
        $stmt_update_factura->bind_param("ssssii", $nuevo_estado_factura, $fecha_pago, $metodo_pago_simulado, $transaccion_id_simulada, $facturaID, $clienteID);
        
        if (!$stmt_update_factura->execute()) {
            throw new Exception("Error al actualizar el estado de la factura: " . $stmt_update_factura->error);
        }
        $stmt_update_factura->close();
        
        if ($factura_info['SitioID'] !== null && strpos(strtolower($factura_info['Descripcion']), 'activación') !== false) {
            $sitioID_asociado = $factura_info['SitioID'];
            $nuevo_estado_sitio = 'activo';

            $sql_update_sitio = "UPDATE SitioWeb SET EstadoServicio = ? WHERE SitioID = ? AND ClienteID = ?";
            $stmt_update_sitio = $conn->prepare($sql_update_sitio);
            $stmt_update_sitio->bind_param("sii", $nuevo_estado_sitio, $sitioID_asociado, $clienteID);
            if (!$stmt_update_sitio->execute()) {
                error_log("Error al actualizar estado del SitioWeb ID " . $sitioID_asociado . ": " . $stmt_update_sitio->error);
            }
            $stmt_update_sitio->close();
        }

        $conn->commit();
        $_SESSION['success_message'] = "¡Factura #" . $facturaID . " pagada exitosamente!";
        header("Location: facturas.php?highlight_factura=" . $facturaID);
        exit();

    } else {
        $conn->rollback();
        $_SESSION['error_message'] = "El proceso de pago no pudo completarse. Inténtalo de nuevo.";
        header("Location: facturas.php?highlight_factura=" . $facturaID);
        exit();
    }

} catch (Exception $e) {
    if ($conn && $conn->ping()) {
        try { $conn->rollback(); } catch (mysqli_sql_exception $ex) {}
    }
    $_SESSION['error_message'] = "Ocurrió un error al procesar el pago: " . $e->getMessage();
    header("Location: facturas.php");
    exit();
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>
