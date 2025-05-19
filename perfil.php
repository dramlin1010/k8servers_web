<?php
session_start();
if (!isset($_SESSION['ClienteID'])) {
    header('Location: login.php');
    exit();
}

include("menu_vertical_usuario.html");

$servidor = "mariadb-host-svc";
$usuario = "daniel";
$password = "Kt3xa6RqSAgdpskCZyuWfX";
$DB = "k8servers";
$conn = new mysqli($servidor, $usuario, $password, $DB);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$ClienteID = $_SESSION['ClienteID'];
$sql = "SELECT nombre, email, imagen FROM Cliente WHERE ClienteID = ?";
$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $ClienteID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
} else {
    session_destroy();
    header('Location: login.php');
    exit();
}

if (empty($usuario['imagen']) || !file_exists($usuario['imagen'])) {
    $usuario['imagen'] = 'imagenes/default_profile.png';
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>
    <link rel="stylesheet" href="css/vertical_menu.css">
    <style>
        body {
            display: flex;
            margin: 0;
        }
        .menu-container {
            width: 250px;
        }
        .content-container {
            flex: 1;
            padding-left: 250px;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .profile-container {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .profile-container img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="menu-container">
        <?php include("menu_vertical_usuario.html"); ?>
    </div>
    <div class="content-container">
        <div class="profile-container">
            <h1>Perfil de Usuario</h1>
            <img src="<?= htmlspecialchars($usuario['imagen']) ?>" alt="Imagen de Perfil">
            <p><strong>Nombre:</strong> <?= htmlspecialchars($usuario['nombre']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
        </div>
    </div>
</body>
</html>
