<?php
include("menu_admin.html");
session_start();

if (!isset($_COOKIE['admin_session']) || $_COOKIE['admin_session'] !== session_id()) {
    header("Location: ../login.php");
    exit();
}

require_once("../conexion.php");

$sql = "SELECT COUNT(*) as total FROM Cliente";
$result = $conn->query($sql);
$total_usuarios = ($result->num_rows > 0) ? $result->fetch_assoc()['total'] : 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../css/panel_admin.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            display: flex;
        }

        .main-content {
            margin-left: 240px; /* Ancho del menú vertical */
            padding: 20px;
            width: calc(100% - 240px);
            min-height: 100vh;
            background-color: #fff;
        }

        .dashboard-card {
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: #f9f9f9;
            text-align: center;
        }

        .dashboard-card h3 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .dashboard-card p {
            font-size: 18px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h1>Bienvenido al Panel de Administración</h1>
        <div class="dashboard-card">
            <h3>Total de Usuarios Registrados</h3>
            <p><?php echo $total_usuarios; ?></p>
        </div>
    </div>
</body>
</html>
