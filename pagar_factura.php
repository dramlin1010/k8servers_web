<?php
session_start();

if (!isset($_SESSION['ClienteID'])) {
    header("Location: login.php");
    exit();
}

require 'conexion.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$ClienteID = $_SESSION['ClienteID'];
$user_message = '';

if (!isset($_POST['factura_id']) || !filter_var($_POST['factura_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['user_message'] = 'Error: ID de factura inválido.';
    header("Location: panel_usuario.php?pago=error");
    exit();
}
$facturaID = (int)$_POST['factura_id'];

$conn->begin_transaction();

try {
    $sql_info = "SELECT CuentaHostingID FROM Factura WHERE FacturaID = ? AND ClienteID = ? AND Estado = 'pendiente' FOR UPDATE"
    $stmt_info = $conn->prepare($sql_info);
    if ($stmt_info === false) throw new Exception("Error preparando consulta info: " . $conn->error);
    $stmt_info->bind_param("ii", $facturaID, $ClienteID);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();

    if ($result_info->num_rows == 1) {
        $factura_data = $result_info->fetch_assoc();
        $cuentaHostingID = $factura_data['CuentaHostingID'];
        $stmt_info->close();

        $sql_update = "UPDATE Factura SET Estado = 'pagado' WHERE FacturaID = ? AND ClienteID = ?";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update === false) throw new Exception("Error preparando update factura: " . $conn->error);
        $stmt_update->bind_param("ii", $facturaID, $ClienteID);
        if ($stmt_update->execute() === FALSE) throw new Exception("Error ejecutando update factura: " . $stmt_update->error);
        $stmt_update->close();
        error_log("Factura $facturaID marcada como pagada para ClienteID $ClienteID.");

        $sql_insert_task = "INSERT INTO Tareas_Aprovisionamiento (ClienteID, CuentaHostingID, Estado) VALUES (?, ?, 'pendiente')";
        $stmt_task = $conn->prepare($sql_insert_task);
        if ($stmt_task === false) throw new Exception("Error preparando insert tarea: " . $conn->error);
        $stmt_task->bind_param("ii", $ClienteID, $cuentaHostingID);
        if ($stmt_task->execute() === FALSE) throw new Exception("Error ejecutando insert tarea: " . $stmt_task->error);
        $new_task_id = $stmt_task->insert_id;
        $stmt_task->close();
        error_log("Tarea de aprovisionamiento $new_task_id creada para ClienteID $ClienteID, CuentaHostingID $cuentaHostingID.");

        $conn->commit();
        $user_message = "¡Pago recibido con éxito! Tu servicio de hosting (Cuenta ID: $cuentaHostingID) se está configurando. Este proceso puede tardar unos minutos. Recibirás una notificación o podrás ver el estado en tu panel.";

    } else {
        $stmt_info->close();
        throw new Exception("Factura no encontrada, ya pagada o no pertenece al usuario.");
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error en transacción de pago/tarea para FacturaID $facturaID, ClienteID $ClienteID: " . $e->getMessage());
    $user_message = "Error al procesar el pago o iniciar la configuración. Por favor, inténtelo de nuevo o contacta a soporte.";
    $_SESSION['user_message'] = $user_message;
    header("Location: panel_usuario.php?pago=error");
    exit();
}

$conn->close();

$_SESSION['user_message'] = $user_message;
header("Location: panel_usuario.php?pago=exitoso");
exit();
?>
