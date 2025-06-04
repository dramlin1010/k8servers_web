<?php
require_once 'config.php';
session_start();
require 'conexion.php';

$ips = $_GET['ips'];
$so = $_GET['so'];
$clienteID = $_SESSION['ClienteID'];
$dominio = $_GET['dominio'];

$precio_base = isset($_GET['precio']) ? floatval($_GET['precio']) : 0;
$disco = isset($_GET['disco']) ? $_GET['disco'] : '';
$detalles = isset($_GET['detalles']) ? $_GET['detalles'] : '';
$PlanNombre = isset($_GET['plan_nombre']) ? $_GET['plan_nombre'] : '';
$plan_id = isset($_GET['id']) ? floatval($_GET['id']) : 0;

$sqlCheckDomain = "SELECT * FROM Plan_Hosting WHERE Dominio = '$dominio'";
$result = $conn->query($sqlCheckDomain);

if ($result->num_rows > 0) {
    header("Location: factura.php?error=dominio_existente&id=$plan_id&name=" . urlencode($PlanNombre) . "&precio=$precio_base&disco=$disco");
    exit();
}

if (!isset($clienteID)) {
    die("Error: Cliente no autenticado.");
}

$incrementoPorIP = 1;
if ($ips > 1) {
    $precioTotal = $precio_base + ($ips - 1) * $incrementoPorIP;
} else {
    $precioTotal = $precio_base;
}

$fechaEmision = date("Y-m-d H:i:s");
$fechaVencimiento = date("Y-m-d H:i:s", strtotime("+1 month"));

$sqlPlan_Hosting = "INSERT INTO Plan_Hosting (NombrePlan, Dominio, SistemaOperativo, Disco, Precio)
                    VALUES ('$PlanNombre', '$dominio', '$so', '$disco', '$precioTotal')";
if ($conn->query($sqlPlan_Hosting) === TRUE) {
    $planHostingID = $conn->insert_id;

    $sqlCuentaHosting = "INSERT INTO Cuenta_Hosting (ClienteID, PlanHostingID, Dominio, FechaInicio, Estado, AnchoBandaUsado)
                         VALUES ('$clienteID', '$planHostingID', '$dominio', '$fechaEmision', 'activo', 0)";
    if ($conn->query($sqlCuentaHosting) === TRUE) {
        $cuentaHostingID = $conn->insert_id;

        $sqlFactura = "INSERT INTO Factura (ClienteID, CuentaHostingID, FechaEmision, FechaVencimiento, SaldoTotal, Descripcion, Estado)
                       VALUES ('$clienteID', '$cuentaHostingID', '$fechaEmision', '$fechaVencimiento', '$precioTotal', '$detalles' , 'pendiente')";
        if ($conn->query($sqlFactura) === TRUE) {
            $facturaID = $conn->insert_id;
            ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Generada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .factura-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .factura-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .factura-header .logo {
            font-size: 24px;
            font-weight: bold;
            color: #9c2531;
        }

        .factura-header .fecha {
            text-align: right;
        }

        .factura-details {
            margin-bottom: 20px;
        }

        .factura-details p {
            margin: 5px 0;
        }

        .factura-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .factura-items th, .factura-items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .factura-items th {
            background-color: #9c2531;
            color: white;
        }

        .total {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .btn-continuar {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #9c2531;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }

        .btn-continuar:hover {
            background-color: #b33644;
        }
    </style>
</head>
<body>
<div class="factura-container">
    <div class="factura-header">
        <div class="logo">Hosting</div>
        <div class="fecha">
            <p><strong>Fecha de Emisión:</strong> <?php echo $fechaEmision; ?></p>
            <p><strong>Fecha de Vencimiento:</strong> <?php echo $fechaVencimiento; ?></p>
        </div>
    </div>

    <div class="factura-details">
        <p><strong>Factura ID:</strong> <?php echo $facturaID; ?></p>
        <p><strong>Cliente ID:</strong> <?php echo $clienteID; ?></p>
        <p><strong>Dominio:</strong> <?php echo $dominio; ?></p>
        <p><strong>Plan:</strong> <?php echo $PlanNombre; ?></p>
        <p><strong>Detalles:</strong> <?php echo $detalles; ?></p>
    </div>

    <table class="factura-items">
        <thead>
        <tr>
            <th>Concepto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><?php echo $PlanNombre; ?></td>
            <td>1</td>
            <td>€<?php echo number_format($precio_base, 2); ?></td>
            <td>€<?php echo number_format($precio_base, 2); ?></td>
        </tr>
        <tr>
            <td>IPs Adicionales</td>
            <td><?php echo $ips - 1; ?></td>
            <td>€<?php echo number_format($incrementoPorIP, 2); ?></td>
            <td>€<?php echo number_format(($ips - 1) * $incrementoPorIP, 2); ?></td>
        </tr>
        </tbody>
    </table>

    <div class="total">Total: €<?php echo number_format($precioTotal, 2); ?></div>

    <a href="panel_usuario.php" class="btn-continuar">Continuar</a>
</div>
</body>
</html>
            <?php
        } else {
            echo "Error al insertar en Factura: " . $conn->error;
        }
    } else {
        echo "Error al insertar en Cuenta_Hosting: " . $conn->error;
    }
} else {
    echo "Error al insertar en Plan_Hosting: " . $conn->error;
}

$conn->close();
?>
