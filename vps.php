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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccion VPS</title>
    <style>
        .menu-vertical {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background-color: #9c2531;
            padding-top: 20px;
            color: white;
            z-index: 999;
        }

        .menu-list {
            list-style: none;
            padding: 0;
        }

        .menu-list li a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
        }

        .menu-list li a:hover {
            background-color: #b33644;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .server-container {
            margin-left: 270px;
            padding: 20px;
        }

        .server-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-gap: 1px;
        }


        .server-item .details {
            flex: 1;
        }

        .server-item .pricing {
            text-align: center;
        }

        .server-item .price {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .order-btn {
            background-color: #9c2531;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .order-btn:hover {
            background-color: #b33644;
        }

        h2 {
            color: #333;
        }
    </style>
</head>
<body>
    <?php
        $paises = ["Estados Unidos", "Canada", "Alemania", "Reino Unido"];

        $paisSeleccionado = isset($_GET['pais']) ? $_GET['pais'] : $paises[0];
    
    $productos = [
        [
            "vCore" => "x1",
            "Ram" => "2048 MB",
            "NVMe" => "10 GB",
            "IP" => "IPv4/IPv6",
            "Puerto" => "1 Gbps Shared",
            "Precio" => "10.00€",
            "ID" => 1
        ],
        [
            "vCore" => "x2",
            "Ram" => "4096 MB",
            "NVMe" => "40 GB",
            "IP" => "IPv4/IPv6",
            "Puerto" => "1 Gbps Shared",
            "Precio" => "18.00€",
            "ID" => 2
        ],
        [
            "vCore" => "x4",
            "Ram" => "8192 MB",
            "NVMe" => "80 GB",
            "IP" => "IPv4/IPv6",
            "Puerto" => "1 Gbps Shared",
            "Precio" => "24.00€",
            "ID" => 3
        ]
    ];
    ?>

    <div class="server-container">
        <h2>Seleccionar País</h2>
        <form method="GET" action="">
            <label for="pais">País:</label>
            <select id="pais" name="pais" onchange="this.form.submit()">
                <?php foreach ($paises as $pais): ?>
                    <option value="<?= htmlspecialchars($pais) ?>" <?= $pais === $paisSeleccionado ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pais) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <h2>Productos disponibles en <?= htmlspecialchars($paisSeleccionado) ?></h2>
        <?php foreach ($productos as $producto): ?>
            <div class="server-item">
                <div class="details">
                    <p><strong>vCore:</strong> <?= htmlspecialchars($producto["vCore"]) ?></p>
                    <p><strong>Ram:</strong> <?= htmlspecialchars($producto["Ram"]) ?></p>
                    <p><strong>NVMe:</strong> <?= htmlspecialchars($producto["NVMe"]) ?></p>
                    <p><strong>IP:</strong> <?= htmlspecialchars($producto["IP"]) ?></p>
                    <p><strong>Puerto:</strong> <?= htmlspecialchars($producto["Puerto"]) ?></p>
                    <p><strong>Ubicación:</strong> <?= htmlspecialchars($paisSeleccionado) ?></p>
                </div>
                <div class="pricing">
                    <p class="price"><?= htmlspecialchars($producto["Precio"]) ?></p>
                    <a href="factura.php?id=<?= $producto["ID"] ?>&pais=<?= urlencode($paisSeleccionado) ?>" class="order-btn">Pedir Ahora</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
