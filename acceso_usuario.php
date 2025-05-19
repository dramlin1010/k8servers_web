<?php
session_start();

$servidor = "mariadb-host-svc";
$usuario = "daniel";
$password = "Kt3xa6RqSAgdpskCZyuWfX";
$DB = "k8servers";
$conexion_DB = new mysqli($servidor, $usuario, $password, $DB);

if ($conexion_DB->connect_error) {
    die("Error de conexión: " . $conexion_DB->connect_error);
}

$email = $_POST["Email"];
$passwd = $_POST["Passwd"];

$sql = "SELECT * FROM Cliente WHERE Email = ?";
$stmt = $conexion_DB->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();

    if (password_verify($passwd, $usuario['Passwd'])) {
        $_SESSION['ClienteID'] = $usuario['ClienteID'];
        $_SESSION['Nombre'] = $usuario['Nombre'];
        $_SESSION['Email'] = $usuario['Email'];

        $token = bin2hex(random_bytes(32));
        $_SESSION['token'] = $token;
        setcookie("session_token", $token, time() + 3600, "/", "", true, true);

        if ($email === "admin@hosting.com") {
            setcookie("admin_session", session_id(), time() + 3600, "/", "", true, true);
            header("Location: admin/panel.php");
        } else {
            header("Location: panel_usuario.php");
        }
        exit();
    } else {
        echo "Correo electronico o Contraseña Incorrecta.";
    }
} else {
    echo "Correo electronico o Contraseña Incorrecta.";
}

$stmt->close();
$conexion_DB->close();
?>
