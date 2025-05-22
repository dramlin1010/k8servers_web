<?php
session_start();

if (!isset($_COOKIE['admin_session']) || !isset($_SESSION['token']) || $_COOKIE['admin_session'] !== session_id() || $_SESSION['token'] !== $_COOKIE['session_token']) {
    if (isset($_SESSION['token'])) unset($_SESSION['token']);
    if (isset($_COOKIE['session_token'])) setcookie("session_token", "", time() - 3600, "/");
    if (isset($_COOKIE['admin_session'])) setcookie("admin_session", "", time() - 3600, "/");
    session_destroy();
    header("Location: ../login.php?error=admin_auth_failed");
    exit();
}

require_once("../conexion.php");
$facturas = [];
$sql_facturas = "SELECT f.FacturaID, f.Descripcion, f.Monto, f.Estado, f.FechaEmision, f.FechaVencimiento, c.Email AS ClienteEmail
                 FROM Factura f
                 JOIN Cliente c ON f.ClienteID = c.ClienteID
                 ORDER BY f.FechaEmision DESC, f.FacturaID DESC";
$result_facturas = $conn->query($sql_facturas);
if ($result_facturas && $result_facturas->num_rows > 0) {
    while($row = $result_facturas->fetch_assoc()) {
        $facturas[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Facturas - Admin k8servers</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Gestionar Facturas</h1>
                    <p>Revisa y administra todas las facturas generadas.</p>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section">
                        <h2 class="section-subtitle">Listado de Facturas</h2>
                        <?php if (empty($facturas)): ?>
                            <div class="empty-state"><p>No hay facturas generadas en el sistema.</p></div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID Factura</th>
                                            <th>Cliente Email</th>
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
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($factura['FacturaID']); ?></td>
                                            <td><?php echo htmlspecialchars($factura['ClienteEmail']); ?></td>
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
                                                <a href="ver_factura_admin.php?id=<?php echo $factura['FacturaID']; ?>" class="btn btn-xs btn-outline">Ver</a>
                                                <?php if(strtolower($factura['Estado']) == 'pendiente'): ?>
                                                <a href="marcar_pagada_admin.php?id=<?php echo $factura['FacturaID']; ?>" class="btn btn-xs btn-success">Marcar Pagada</a>
                                                <?php endif; ?>
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
