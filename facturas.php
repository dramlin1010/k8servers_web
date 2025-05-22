<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    $_SESSION['error_message'] = "Acceso no autorizado. Por favor, inicia sesión.";
    header("Location: login.php");
    exit();
}

if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy();
    setcookie("session_token", "", time() - 3600, "/");
    $_SESSION['error_message'] = "Token de sesión inválido. Por favor, inicia sesión de nuevo.";
    header("Location: login.php");
    exit();
}

$clienteID = $_SESSION['ClienteID'];
$highlightFacturaID = isset($_GET['highlight_factura']) ? (int)$_GET['highlight_factura'] : null;

$facturas = [];
$sql = "SELECT FacturaID, Descripcion, Monto, Estado, FechaEmision, FechaVencimiento, SitioID 
        FROM Factura 
        WHERE ClienteID = ? 
        ORDER BY FechaEmision DESC, FacturaID DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $clienteID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $facturas[] = $row;
        }
    }
    $stmt->close();
} else {
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Facturas - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Mis Facturas</h1>
                    <p>Consulta el historial y estado de tus facturas.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php
                    if (isset($_SESSION['success_message'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                        unset($_SESSION['success_message']);
                    }
                    if (isset($_SESSION['error_message'])) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                        unset($_SESSION['error_message']);
                    }
                    ?>
                    <section class="content-section">
                        <h2 class="section-subtitle">Historial de Facturación</h2>
                        <?php if (empty($facturas)): ?>
                            <div class="empty-state">
                                <i class="icon-big">&#128179;</i>
                                <p>Aún no tienes ninguna factura generada.</p>
                                <p>Cuando contrates un servicio, tus facturas aparecerán aquí.</p>
                                <a href="contratar_servicio.php" class="btn btn-primary">Contratar Hosting</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID Factura</th>
                                            <th>Descripción</th>
                                            <th>Monto</th>
                                            <th>Estado</th>
                                            <th>Fecha Emisión</th>
                                            <th>Fecha Vencimiento</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($facturas as $factura): ?>
                                            <tr class="<?php echo ($highlightFacturaID === (int)$factura['FacturaID']) ? 'highlight-row' : ''; ?>">
                                                <td>#<?php echo htmlspecialchars($factura['FacturaID']); ?></td>
                                                <td><?php echo htmlspecialchars($factura['Descripcion']); ?></td>
                                                <td><?php echo number_format($factura['Monto'], 2, ',', '.'); ?> €</td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower(htmlspecialchars($factura['Estado'])); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($factura['Estado'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $factura['FechaEmision'] ? date("d/m/Y", strtotime($factura['FechaEmision'])) : 'N/A'; ?></td>
                                                <td><?php echo $factura['FechaVencimiento'] ? date("d/m/Y", strtotime($factura['FechaVencimiento'])) : 'N/A'; ?></td>
                                                <td>
                                                    <?php if (strtolower($factura['Estado']) === 'pendiente'): ?>
                                                        <form action="pagar_factura.php" method="POST" style="display:inline;">
                                                            <input type="hidden" name="factura_id" value="<?php echo $factura['FacturaID']; ?>">
                                                            <button class="btn btn-xs btn-primary" type="submit">Pagar</button>
                                                        </form>
                                                    <?php elseif (strtolower($factura['Estado']) === 'pagado'): ?>
                                                        <span class="btn btn-xs btn-success disabled">Pagado</span>
                                                    <?php endif; ?>
                                                    <a href="ver_factura_detalle.php?id=<?php echo $factura['FacturaID']; ?>" class="btn btn-xs btn-outline" style="margin-left: 5px;">Ver</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </main>
    </div>

<script>
    const themeTogglePanel = document.getElementById('theme-toggle-panel');
    const bodyPanel = document.body;

    function applyThemePanel(theme) {
        bodyPanel.classList.remove('light-mode', 'dark-mode');
        bodyPanel.classList.add(theme + '-mode');
        const sunIconPanel = themeTogglePanel ? themeTogglePanel.querySelector('.sun-icon') : null;
        const moonIconPanel = themeTogglePanel ? themeTogglePanel.querySelector('.moon-icon') : null;

        if (theme === 'light') {
            if(sunIconPanel) sunIconPanel.style.display = 'none';
            if(moonIconPanel) moonIconPanel.style.display = 'block';
        } else {
            if(sunIconPanel) sunIconPanel.style.display = 'block';
            if(moonIconPanel) moonIconPanel.style.display = 'none';
        }
        localStorage.setItem('theme', theme);
    }

    if (themeTogglePanel) {
        themeTogglePanel.addEventListener('click', () => {
            let newTheme = bodyPanel.classList.contains('light-mode') ? 'dark' : 'light';
            applyThemePanel(newTheme);
        });
    }

    const savedThemePanel = localStorage.getItem('theme') || 'dark';
    applyThemePanel(savedThemePanel);

    document.addEventListener('DOMContentLoaded', () => {
        const animatedSections = document.querySelectorAll('.animated-section');
        if (animatedSections.length > 0) {
            const observerOptions = { root: null, rootMargin: '0px', threshold: 0.1 };
            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            animatedSections.forEach(section => { observer.observe(section); });
        }
    });
</script>
</body>
</html>
