<?php
require_once '../config.php';

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
$sitios = [];
$sql_sitios = "SELECT sw.SitioID, sw.DominioCompleto, sw.EstadoServicio, sw.FechaContratacion, c.Email AS ClienteEmail
               FROM SitioWeb sw
               JOIN Cliente c ON sw.ClienteID = c.ClienteID
               ORDER BY sw.FechaContratacion DESC";
$result_sitios = $conn->query($sql_sitios);
if ($result_sitios && $result_sitios->num_rows > 0) {
    while($row = $result_sitios->fetch_assoc()) {
        $sitios[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Sitios Web - Admin k8servers</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Gestionar Sitios Web</h1>
                    <p>Supervisa y administra los sitios web de los clientes.</p>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section">
                        <h2 class="section-subtitle">Listado de Sitios Web</h2>
                         <?php if (empty($sitios)): ?>
                            <div class="empty-state"><p>No hay sitios web registrados.</p></div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID Sitio</th>
                                            <th>Dominio Completo</th>
                                            <th>Cliente Email</th>
                                            <th>Estado</th>
                                            <th>Fecha Contratación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sitios as $sitio): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sitio['SitioID']); ?></td>
                                            <td><?php echo htmlspecialchars($sitio['DominioCompleto']); ?></td>
                                            <td><?php echo htmlspecialchars($sitio['ClienteEmail']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', htmlspecialchars($sitio['EstadoServicio']))); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($sitio['EstadoServicio']))); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date("d/m/Y", strtotime($sitio['FechaContratacion'])); ?></td>
                                            <td>
                                                <a href="ver_sitio_admin.php?id=<?php echo $sitio['SitioID']; ?>" class="btn btn-xs btn-outline">Ver Detalles</a>
                                                
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
