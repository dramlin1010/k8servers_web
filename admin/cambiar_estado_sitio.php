<?php
session_start();
require_once("../conexion.php");

if (!isset($_COOKIE['admin_session']) || !isset($_SESSION['token']) || $_COOKIE['admin_session'] !== session_id() || $_SESSION['token'] !== $_COOKIE['session_token']) {
    if (isset($_SESSION['token'])) unset($_SESSION['token']);
    if (isset($_COOKIE['session_token'])) setcookie("session_token", "", time() - 3600, "/");
    if (isset($_COOKIE['admin_session'])) setcookie("admin_session", "", time() - 3600, "/");
    session_destroy();
    $_SESSION['error_message_sitio_detalle'] = "Acceso no autorizado para cambiar estado.";
    header("Location: ../login.php?error=admin_auth_failed");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sitio_id']) && isset($_POST['nuevo_estado'])) {
    $sitioID_cambiar = filter_var($_POST['sitio_id'], FILTER_VALIDATE_INT);
    $nuevo_estado = trim($_POST['nuevo_estado']);

    $estados_permitidos = ['activo', 'suspendido', 'cancelado', 'pendiente_pago', 'pendiente_aprovisionamiento'];

    if ($sitioID_cambiar === false || $sitioID_cambiar <= 0) {
        $_SESSION['error_message_sitio_detalle'] = "ID de sitio no válido.";
        header("Location: ver_sitio_admin.php?id=" . ($sitioID_cambiar ?: ''));
        exit();
    }
    if (!in_array($nuevo_estado, $estados_permitidos)) {
        $_SESSION['error_message_sitio_detalle'] = "Estado seleccionado no válido.";
        header("Location: ver_sitio_admin.php?id=" . $sitioID_cambiar);
        exit();
    }

    $stmt_check = $conn->prepare("SELECT ClienteID FROM SitioWeb WHERE SitioID = ?");
    if ($stmt_check) {
        $stmt_check->bind_param("i", $sitioID_cambiar);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows === 0) {
            $_SESSION['error_message_sitio_list'] = "Sitio ID #" . $sitioID_cambiar . " no encontrado.";
            $stmt_check->close();
            $conn->close();
            header("Location: gestionar_sitios_admin.php");
            exit();
        }
        $stmt_check->close();
    }


    $sql_update = "UPDATE SitioWeb SET EstadoServicio = ? WHERE SitioID = ?";
    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update) {
        $stmt_update->bind_param("si", $nuevo_estado, $sitioID_cambiar);
        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $_SESSION['success_message_sitio_detalle'] = "Estado del sitio ID #" . $sitioID_cambiar . " actualizado a '" . htmlspecialchars($nuevo_estado) . "'.";
            } else {
                $_SESSION['info_message_sitio_detalle'] = "El estado del sitio ID #" . $sitioID_cambiar . " ya era '" . htmlspecialchars($nuevo_estado) . "' o el sitio no fue encontrado.";
            }
        } else {
            $_SESSION['error_message_sitio_detalle'] = "Error al actualizar el estado del sitio: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $_SESSION['error_message_sitio_detalle'] = "Error al preparar la actualización del estado: " . $conn->error;
    }
} else {
    $_SESSION['error_message_sitio_list'] = "Solicitud no válida para cambiar estado del sitio.";
    header("Location: gestionar_sitios_admin.php");
    exit();
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
header("Location: ver_sitio_admin.php?id=" . $sitioID_cambiar);
exit();
?>
