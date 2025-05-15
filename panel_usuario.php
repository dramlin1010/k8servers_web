<?php
session_start();

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy();
    setcookie("session_token", "", time() - 3600, "/");
    header("Location: login.php");
    exit();
}

include("menu_vertical_usuario.html");
require 'conexion.php';

$ClienteID = $_SESSION['ClienteID'];

$sql = "SELECT FacturaID, Descripcion, Estado, FechaVencimiento FROM Factura WHERE ClienteID = $ClienteID";
$result = $conn->query($sql);

$facturas = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facturas[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario</title>
    <link rel="stylesheet" href="menu_vertical.css">
    <style>
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
}

.sidebar {
    width: 250px;
    height: 100vh;
    background-color: #9c2531;
    color: white;
    position: fixed;
    padding: 20px 0;
    overflow-y: auto;
}

.logo {
    text-align: center;
    margin-bottom: 30px;
}

.logo h2 {
    margin: 0;
}

nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

nav ul li {
    padding: 15px 25px;
    cursor: pointer;
}

nav ul li a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    font-size: 16px;
}

nav ul li .icon {
    margin-right: 15px;
    font-size: 18px;
}

.section-title {
    padding: 10px 25px;
    font-size: 14px;
    opacity: 0.7;
}

nav ul li:hover {
    background-color: #b33644;
}

nav ul .dropdown ul.submenu {
    display: none;
    background-color: #b33644;
}

.dropdown:hover .submenu {
    display: block;
}

.submenu li {
    padding: 10px 40px;
}

.submenu li:hover {
    background-color: #c84d5e;
}

.content-wrapper {
    margin-left: 250px;
    padding: 20px;
    width: calc(100% - 250px);
    background-color: #f9f9f9;
    overflow-x: hidden;
}

.main-content {
    background-color: #ffffff;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 20px;
    width: 100%;
}

h1 {
    font-size: 1.8rem;
    color: #444;
    margin-bottom: 10px;
    text-align: center;
}

table {
    width: 100%;
    max-width: 100%;
    table-layout: auto;
    margin-top: 20px;
    border-collapse: collapse;
}

table, th, td {
    border: 1px solid #ddd;
}

th, td {
    padding: 5px;
    text-align: left;
    white-space: nowrap;
}

th {
    background-color: #f4f4f4;
}

.btn-pagar {
    display: inline-block;
    padding: 10px 15px;
    background-color: #9c2531;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-align: center;
    font-size: 0.9rem;
}

.btn-pagar:hover {
    background-color: #b33644;
}

.empty-message {
    text-align: center;
    font-size: 1rem;
    color: #777;
}

.btn-pagado {
    display: inline-block;
    padding: 10px 15px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    text-align: center;
    font-size: 0.9rem;
    cursor: not-allowed;
}

.btn-pagado:hover {
    background-color: #45a049;
}


    </style>
</head>
<body>
    <div class="sidebar">
        <?php include("menu_vertical_usuario.html"); ?>
    </div>

    <div class="content-wrapper">
        <div class="main-content">
            <h1>Mis Facturas</h1>
            <?php if (empty($facturas)): ?>
                <p class="empty-message">No tienes facturas pendientes ni VPS contratados.</p>
            <?php else: ?>
                <table>
    <thead>
        <tr>
            <th>Descripción</th>
            <th>Estado</th>
            <th>Fecha de Renovación</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($facturas as $factura): ?>
            <tr>
                <td><?php echo htmlspecialchars($factura['Descripcion']); ?></td>
                <td><?php echo htmlspecialchars($factura['Estado']); ?></td>
                <td><?php echo $factura['FechaVencimiento'] ?: 'N/A'; ?></td>
                <td>
                    <?php if ($factura['Estado'] === 'pendiente'): ?>
                        <form action="pagar_factura.php" method="POST">
                            <input type="hidden" name="factura_id" value="<?php echo $factura['FacturaID']; ?>">
                            <button class="btn-pagar" type="submit">Pagar</button>
                        </form>
                    <?php else: ?>
                        <span class="btn-pagado">Pagado</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
