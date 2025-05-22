<?php
session_start();
require_once("../conexion.php");

if (!isset($_COOKIE['admin_session']) || !isset($_SESSION['token']) || $_COOKIE['admin_session'] !== session_id() || $_SESSION['token'] !== $_COOKIE['session_token']) {
    if (isset($_SESSION['token'])) unset($_SESSION['token']);
    if (isset($_COOKIE['session_token'])) setcookie("session_token", "", time() - 3600, "/");
    if (isset($_COOKIE['admin_session'])) setcookie("admin_session", "", time() - 3600, "/");
    session_destroy();
    $_SESSION['error_message_sitio_list'] = "Acceso no autorizado para eliminar sitio.";
    header("Location: ../login.php?error=admin_auth_failed");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sitio_id_eliminar'])) {
    $sitioID_eliminar = filter_var($_POST['sitio_id_eliminar'], FILTER_VALIDATE_INT);

    if ($sitioID_eliminar === false || $sitioID_eliminar <= 0) {
        $_SESSION['error_message_sitio_list'] = "ID de sitio no válido para eliminar.";
        header("Location: gestionar_sitios_admin.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        // Opcional: Antes de borrar el SitioWeb,se podria manejar las facturas asociadas
        // Por ejemplo, marcarlas como canceladas o desvincularlas (SitioID = NULL)
        // $stmt_update_facturas = $conn->prepare("UPDATE Factura SET Estado = 'cancelado_sitio_eliminado' WHERE SitioID = ? AND Estado = 'pendiente'");
        // $stmt_update_facturas->bind_param("i", $sitioID_eliminar);
        // $stmt_update_facturas->execute();
        // $stmt_update_facturas->close();
        // O simplemente dejar que ON DELETE SET NULL en la FK de Factura.SitioID haga su trabajo.

        // Opcional: Eliminar tickets asociados si la FK tiene ON DELETE CASCADE o hacerlo manualmente
        // $stmt_delete_ticket_mensajes = $conn->prepare("DELETE FROM Mensaje_Ticket WHERE TicketID IN (SELECT TicketID FROM Ticket_Soporte WHERE SitioID = ?)");
        // $stmt_delete_ticket_mensajes->bind_param("i", $sitioID_eliminar);
        // $stmt_delete_ticket_mensajes->execute();
        // $stmt_delete_ticket_mensajes->close();
        // $stmt_delete_tickets = $conn->prepare("DELETE FROM Ticket_Soporte WHERE SitioID = ?");
        // $stmt_delete_tickets->bind_param("i", $sitioID_eliminar);
        // $stmt_delete_tickets->execute();
        // $stmt_delete_tickets->close();


        // Eliminar el sitio web
        $stmt_delete_sitio = $conn->prepare("DELETE FROM SitioWeb WHERE SitioID = ?");
        if ($stmt_delete_sitio) {
            $stmt_delete_sitio->bind_param("i", $sitioID_eliminar);
            if ($stmt_delete_sitio->execute()) {
                if ($stmt_delete_sitio->affected_rows > 0) {
                    $_SESSION['success_message_sitio_list'] = "Sitio Web ID #" . $sitioID_eliminar . " eliminado exitosamente.";
                    // SE PODRIA DISPARAR LA LOGICA PARA ELIMINAR LOS ARCHIVOS DEL SERVIDOR,
                    // LA BASE DE DATOS DEL USUARIO, CONFIGURACIONES DE DNS/VHOST, ETC.
                    // Para la infra de Kubernetes.
                    // Ejemplo: trigger_server_cleanup_for_site($sitioID_eliminar);
                } else {
                    $_SESSION['error_message_sitio_list'] = "No se encontró el sitio ID #" . $sitioID_eliminar . " o ya fue eliminado.";
                }
            } else {
                $_SESSION['error_message_sitio_list'] = "Error al eliminar el sitio web: " . $stmt_delete_sitio->error;
            }
            $stmt_delete_sitio->close();
        } else {
             $_SESSION['error_message_sitio_list'] = "Error al preparar la eliminación del sitio: " . $conn->error;
        }
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error_message_sitio_list'] = "Error de base de datos al eliminar sitio: " . $e->getMessage();
    }

} else {
    $_SESSION['error_message_sitio_list'] = "Solicitud no válida para eliminar sitio.";
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
header("Location: gestionar_sitios_admin.php");
exit();
?>
