<?php
session_start();
require_once("../conexion.php");

if (!isset($_COOKIE['admin_session']) || !isset($_SESSION['token']) || $_COOKIE['admin_session'] !== session_id() || $_SESSION['token'] !== $_COOKIE['session_token']) {
    if (isset($_SESSION['token'])) unset($_SESSION['token']);
    if (isset($_COOKIE['session_token'])) setcookie("session_token", "", time() - 3600, "/");
    if (isset($_COOKIE['admin_session'])) setcookie("admin_session", "", time() - 3600, "/");
    session_destroy();
    $_SESSION['error_message_user_list'] = "Acceso no autorizado para eliminar.";
    header("Location: ../login.php?error=admin_auth_failed");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cliente_id'])) {
    $clienteID_eliminar = filter_var($_POST['cliente_id'], FILTER_VALIDATE_INT);

    if ($clienteID_eliminar === false || $clienteID_eliminar <= 0) {
        $_SESSION['error_message_user_list'] = "ID de usuario no válido para eliminar.";
        header("Location: gestionar_usuarios.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        $stmt_delete = $conn->prepare("DELETE FROM Cliente WHERE ClienteID = ?");
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $clienteID_eliminar);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $_SESSION['success_message_user_list'] = "Usuario ID #" . $clienteID_eliminar . " eliminado exitosamente.";
                } else {
                    $_SESSION['error_message_user_list'] = "No se encontró el usuario ID #" . $clienteID_eliminar . " o ya fue eliminado.";
                }
            } else {
                $_SESSION['error_message_user_list'] = "Error al eliminar el usuario: " . $stmt_delete->error;
            }
            $stmt_delete->close();
        } else {
             $_SESSION['error_message_user_list'] = "Error al preparar la eliminación: " . $conn->error;
        }
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error_message_user_list'] = "Error de base de datos al eliminar usuario: " . $e->getMessage();
    }

} else {
    $_SESSION['error_message_user_list'] = "Solicitud no válida para eliminar usuario.";
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
header("Location: gestionar_usuarios.php");
exit();
?>
