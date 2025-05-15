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

$plan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$servers = [
    1 => [ 'id' => 1, 'name' => 'Plan Basico', 'price' => 10.00, 'disco' => '10GB' , 'detalles' => 'vCore: x1, RAM: 2048MB, NVMe: 10GB, IPs: 1, Virtualización: KVM'],
    2 => [ 'id' => 2, 'name' => 'Plan Intermedio', 'price' => 18.00, 'disco' => '40GB' , 'detalles' => 'vCore: x2, RAM: 4096MB, NVMe: 40GB, IPs: 1, Virtualización: KVM'],
    3 => [ 'id' => 3, 'name' => 'Plan Profesional', 'price' => 24.00, 'disco' => '80GB' , 'detalles' => 'vCore: x4 RAM: 8192MB, NVMe: 80GB, IPs: 1, Virtualización: KVM'],
    4 => [ 'id' => 4, 'name' => 'Dedicated Core Intel Core i7 Plan 1', 'price' => 80.00, 'disco' => '40GB' , 'detalles' => 'Intel® Core™ i7-4770 | 32GB DDR4 | NVMe | 40GB'],
    5 => [ 'id' => 5, 'name' => 'Dedicated Core Intel Core i7 Plan 2', 'price' => 100.00, 'disco' => '40GB' , 'detalles' => 'Intel® Core™ i7-9700 DDR4 | 32GB DDR4 | NVMe | 40GB'],
    6 => [ 'id' => 6, 'name' => 'Dedicated Core AMD Ryzen Plan 1', 'price' => 150.00, 'disco' => '40GB' , 'detalles' => 'Ryzen™ 9 3950X | 32GB DDR4 | NVMe | 40GB'],
    7 => [ 'id' => 7, 'name' => 'Dedicated Core AMD Ryzen Plan 2', 'price' => 250.00, 'disco' => '40GB' , 'detalles' => 'AMD Ryzen™ 9 7950X | 32GB DDR4 | NvMe | 40GB'],
    8 => [ 'id' => 8, 'name' => 'Dedicated Servers RAM Plan 1', 'price' => 90.00, 'disco' => '40GB' , 'detalles' => 'Intel® Xeon® Processor E5-2690 v3 | 64GB DDR4 | NVMe | 40GB'],
    9 => [ 'id' => 9, 'name' => 'Dedicated Servers RAM Plan 2', 'price' => 130.00, 'disco' => '40GB' , 'detalles' => '2x Intel® Xeon® E5-2699 v3 | 64GB RAM | NVMe | 40GB'],
];

if (!isset($servers[$plan_id])) {
   die("Servidor no encontrado.");
}

$server = $servers[$plan_id];

$errorMessage = '';
if (isset($_GET['error']) && $_GET['error'] == 'dominio_existente') {
    $errorMessage = 'El dominio ya existe, por favor elige otro nombre de dominio.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Pedido - <?php echo htmlspecialchars($server['name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 500px;
            margin: auto;
            background: #f4f4f4;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-bottom: 10px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .submit-btn {
            background-color: #9c2531;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn:hover {
            background-color: #b33644;
        }
        .plan-summary {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .plan-summary h3 {
            margin: 0;
            font-size: 1.5em;
            color: #333;
        }
        .plan-summary p {
            margin: 10px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <h2>Configurar Pedido: <?php echo htmlspecialchars($server['name']); ?></h2>
    <?php if ($errorMessage): ?>
        <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <!-- Resumen del plan seleccionado -->
    <div class="plan-summary">
        <h3><?php echo htmlspecialchars($server['name']); ?></h3>
        <p><strong>Detalles:</strong> <?php echo htmlspecialchars($server['detalles']); ?></p>
        <p><strong>Precio:</strong> €<?php echo number_format($server['price'], 2); ?></p>
    </div>

    <form action="procesar_pedido.php" method="GET">
        <input type="hidden" name="precio" value="<?php echo htmlspecialchars($server['price']); ?>">
        <input type="hidden" name="plan_nombre" value="<?php echo htmlspecialchars($server['name']); ?>">

        <label for="ips">Cantidad de IPs:</label>
        <input type="number" id="ips" name="ips" min="1" max="10" required>

        <label for="so">Sistema Operativo:</label>
        <select id="so" name="so" required>
            <option value="Ubuntu">Ubuntu</option>
            <option value="Debian">Debian</option>
            <option value="CentOS">CentOS</option>
            <option value="Windows Server">Windows Server</option>
        </select>
        
        <label for="dominio">Dominio: </label>
        <input type="text" id="dominio" name="dominio" maxlength="30" required>

        <button type="submit" class="submit-btn">Proceder con el Pedido</button>

        <script>
            const precio = <?php echo json_encode($server['price']); ?>;
            const plan_nombre = <?php echo json_encode($server['name']); ?>;
            const id = <?php echo json_encode($server['id']); ?>;
            const disco = <?php echo json_encode($server['disco']); ?>;
            const detalles = <?php echo json_encode($server['detalles']); ?>;

            document.querySelector('form').addEventListener('submit', function(event) {
                const ips = document.getElementById('ips').value;
                const so = document.getElementById('so').value;
                const dominio = document.getElementById('dominio').value;

                const url = `procesar_pedido.php?id=${id}&detalles=${encodeURIComponent(detalles)}&ips=${encodeURIComponent(ips)}&so=${encodeURIComponent(so)}&dominio=${encodeURIComponent(dominio)}&precio=${encodeURIComponent(precio)}&disco=${encodeURIComponent(disco)}&plan_nombre=${encodeURIComponent(plan_nombre)}`;

                window.location.href = url;
                event.preventDefault();
            });
        </script>
    </form>
</body>
</html>
