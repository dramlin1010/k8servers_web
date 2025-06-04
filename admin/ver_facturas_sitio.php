<?php
require_once '../config.php';

session_start();
require_once("../conexion.php");

if (!isset($_COOKIE['admin_session']) || !isset($_SESSION['token']) || $_COOKIE['admin_session'] !== session_id() || $_SESSION['token'] !== $_COOKIE['session_token']) {
    if (isset($_SESSION['token'])) unset($_SESSION['token']);
    if (isset($_COOKIE['session_token'])) setcookie("session_token", "", time() - 3600, "/");
    if (isset($_COOKIE['admin_session'])) setcookie("admin_session", "", time() - 3600, "/");
    session_destroy();
    header("Location: ../login.php?error=admin_auth_failed");
    exit();
}

$facturas_sitio = [];
$sitio_info = null;
$error_message_facturas_sitio = null;

if (!isset($_GET['sitio_id']) || !filter_var($_GET['sitio_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_sitio_list'] = "ID de sitio no válido para ver facturas.";
    header("Location: gestionar_sitios_admin.php");
    exit();
}
$sitioID_param = (int)$_GET['sitio_id'];

$stmt_sitio_info = $conn->prepare("SELECT DominioCompleto, ClienteID FROM SitioWeb WHERE SitioID = ?");
if ($stmt_sitio_info) {
    $stmt_sitio_info->bind_param("i", $sitioID_param);
    $stmt_sitio_info->execute();
    $result_sitio_info = $stmt_sitio_info->get_result();
    if ($result_sitio_info->num_rows === 1) {
        $sitio_info = $result_sitio_info->fetch_assoc();
    } else {
        $_SESSION['error_message_sitio_list'] = "Sitio web no encontrado.";
        $stmt_sitio_info->close();
        $conn->close();
        header("Location: gestionar_sitios_admin.php");
        exit();
    }
    $stmt_sitio_info->close();
} else {
    $error_message_facturas_sitio = "Error al cargar información del sitio: " . $conn->error;
}


if ($sitio_info) {
    $sql_facturas = "SELECT FacturaID, Descripcion, Monto, Estado, FechaEmision, FechaVencimiento 
                     FROM Factura 
                     WHERE SitioID = ? 
                     ORDER BY FechaEmision DESC, FacturaID DESC";
    $stmt_facturas = $conn->prepare($sql_facturas);
    if ($stmt_facturas) {
        $stmt_facturas->bind_param("i", $sitioID_param);
        $stmt_facturas->execute();
        $result_facturas = $stmt_facturas->get_result();
        if ($result_facturas->num_rows > 0) {
            while($row = $result_facturas->fetch_assoc()) {
                $facturas_sitio[] = $row;
            }
        }
        $stmt_facturas->close();
    } else {
        $error_message_facturas_sitio = "Error al preparar la consulta de facturas: " . $conn->error;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas de <?php echo $sitio_info ? htmlspecialchars($sitio_info['DominioCompleto']) : 'Sitio'; ?> - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Facturas del Sitio Web</h1>
                    <?php if ($sitio_info): ?>
                    <p>Mostrando facturas para <strong><?php echo htmlspecialchars($sitio_info['DominioCompleto']); ?></strong> (Sitio ID: <?php echo $sitioID_param; ?>)</p>
                    <?php endif; ?>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php if ($error_message_facturas_sitio): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message_facturas_sitio); ?></div>
                    <?php endif; ?>
                    <section class="content-section">
                        <h2 class="section-subtitle">Historial de Facturación del Sitio</h2>
                        <?php if (empty($facturas_sitio) && $sitio_info): ?>
                            <div class="empty-state">
                                <i class="icon-big">&#128179;</i>
                                <p>No hay facturas asociadas directamente a este sitio web.</p>
                            </div>
                        <?php elseif ($sitio_info): ?>
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
                                        <?php foreach ($facturas_sitio as $factura): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($factura['FacturaID']); ?></td>
                                            <td><?php echo htmlspecialchars($factura['Descripcion']); ?></td>
                                            <td><?php echo number_format($factura['Monto'], 2, ',', '.'); ?> €</td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(htmlspecialchars($factura['Estado'])); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($factura['Estado'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date("d/m/Y", strtotime($factura['FechaEmision'])); ?></td>
                                            <td><?php echo $factura['FechaVencimiento'] ? date("d/m/Y", strtotime($factura['FechaVencimiento'])) : 'N/A'; ?></td>
                                            <td>
                                                <a href="ver_factura_admin.php?id=<?php echo $factura['FacturaID']; ?>" class="btn btn-xs btn-outline">Ver Detalle</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif (!$sitio_info && !$error_message_facturas_sitio): ?>
                             <div class="empty-state"><p>No se pudo cargar la información del sitio.</p></div>
                        <?php endif; ?>
                    </section>
                    <div style="margin-top: 30px;">
                        <a href="ver_sitio_admin.php?id=<?php echo $sitioID_param; ?>" class="btn btn-outline">&larr; Volver al Detalle del Sitio</a>
                        <a href="gestionar_sitios_admin.php" class="btn btn-outline" style="margin-left:10px;">&larr; Volver al Listado de Sitios</a>
                    </div>
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
