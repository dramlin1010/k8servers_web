<?php
session_start();

$servidor = "mariadb-host-svc";
$usuario_db = "daniel";
$password_db = "Kt3xa6RqSAgdpskCZyuWfX";
$DB = "k8servers";
$conexion_DB = new mysqli($servidor, $usuario_db, $password_db, $DB);

if ($conexion_DB->connect_error) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error</title><link rel="stylesheet" href="css/styles.css"></head><body style="display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; background-color: #0f172a; color: #e2e8f0;">';
    echo '<div class="alert alert-danger" style="padding: 20px; border-radius: 8px; max-width: 400px; text-align:center;">Error de conexión al sistema. Por favor, inténtalo más tarde.</div>';
    echo '</body></html>';
    die();
}

$email = $_POST["Email"] ?? '';
$passwd = $_POST["Passwd"] ?? '';
$error_message = "";

if (empty($email) || empty($passwd)) {
    $error_message = "Debes ingresar tu correo electrónico y contraseña.";
} else {
    $sql = "SELECT ClienteID, Nombre, Email, Passwd FROM Cliente WHERE Email = ?";
    $stmt = $conexion_DB->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $usuario_data = $resultado->fetch_assoc();

            if (password_verify($passwd, $usuario_data['Passwd'])) {
                $_SESSION['ClienteID'] = $usuario_data['ClienteID'];
                $_SESSION['NombreCliente'] = $usuario_data['Nombre'];
                $_SESSION['EmailCliente'] = $usuario_data['Email'];

                $token = bin2hex(random_bytes(32));
                $_SESSION['token'] = $token;
                setcookie("session_token", $token, time() + 3600, "/", "", true, true);

                if ($email === "admin@k8servers.es") {
                    setcookie("admin_session", session_id(), time() + 3600, "/", "", true, true);
                    header("Location: admin/panel.php");
                } else {
                    header("Location: panel_usuario.php");
                }
                $stmt->close();
                $conexion_DB->close();
                exit();
            } else {
                $error_message = "Correo electrónico o Contraseña Incorrecta.";
            }
        } else {
            $error_message = "Correo electrónico o Contraseña Incorrecta.";
        }
        if($stmt) $stmt->close();
    } else {
        $error_message = "Error al preparar la consulta. Inténtalo más tarde.";
    }
}
$conexion_DB->close();

if (!empty($error_message)) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error de Acceso</title><link rel="stylesheet" href="css/styles.css"></head><body style="display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:100vh; margin:0; background-color: #0f172a; color: #e2e8f0; font-family: Poppins, sans-serif;">';
    echo '<div class="alert alert-danger" style="padding: 20px; border-radius: 8px; max-width: 400px; text-align:center; margin-bottom: 20px;">' . htmlspecialchars($error_message) . '</div>';
    echo '<a href="login.php" class="btn btn-primary" style="text-decoration:none;">Volver a Intentar</a>';
    echo '</body></html>';
    exit();
}
?>
