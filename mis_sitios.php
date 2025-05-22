<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    $_SESSION['error_message'] = "Acceso no autorizado.";
    header("Location: login.php");
    exit();
}

if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy();
    setcookie("session_token", "", time() - 3600, "/");
    $_SESSION['error_message'] = "Token de sesión inválido.";
    header("Location: login.php");
    exit();
}

$clienteID = $_SESSION['ClienteID'];
$sitios = [];

$sql_sitios = "SELECT SitioID, DominioCompleto, EstadoServicio, FechaContratacion, FechaProximaRenovacion 
               FROM SitioWeb 
               WHERE ClienteID = ? 
               ORDER BY FechaContratacion DESC";
$stmt_sitios = $conn->prepare($sql_sitios);

if ($stmt_sitios) {
    $stmt_sitios->bind_param("i", $clienteID);
    $stmt_sitios->execute();
    $result_sitios = $stmt_sitios->get_result();
    if ($result_sitios->num_rows > 0) {
        while ($row_sitio = $result_sitios->fetch_assoc()) {
            $sitios[] = $row_sitio;
        }
    }
    $stmt_sitios->close();
} else {
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Sitios - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Gestionar Mis Sitios Web</h1>
                    <p>Administra tus dominios, archivos y configuraciones.</p>
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
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 class="section-subtitle" style="margin-bottom: 0; border-bottom: none;">Tus Sitios Alojados</h2>
                            <?php
                            $tiene_servicio_activo_o_pendiente = false;
                            foreach ($sitios as $sitio_check) {
                                if (in_array(strtolower($sitio_check['EstadoServicio']), ['activo', 'pendiente_pago', 'pendiente_aprovisionamiento'])) {
                                    $tiene_servicio_activo_o_pendiente = true;
                                    break;
                                }
                            }
                            if (!$tiene_servicio_activo_o_pendiente) {
                                echo '<a href="contratar_servicio.php" class="btn btn-primary"><i class="icon" style="margin-right: 5px;">&#43;</i> Activar Hosting</a>';
                            }
                            ?>
                        </div>

                        <?php if (empty($sitios)): ?>
                            <div class="empty-state">
                                <i class="icon-big">&#128187;</i>
                                <p>Aún no has activado tu servicio de hosting.</p>
                                <p>Nuestro plan de hosting te da todo lo que necesitas para lanzar tu web.</p>
                                <a href="contratar_servicio.php" class="btn btn-primary">Activar Mi Hosting Ahora</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Dominio</th>
                                            <th>Estado del Servicio</th>
                                            <th>Contratado el</th>
                                            <th>Próxima Renovación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sitios as $sitio): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($sitio['DominioCompleto']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', htmlspecialchars($sitio['EstadoServicio']))); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($sitio['EstadoServicio']))); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $sitio['FechaContratacion'] ? date("d/m/Y", strtotime($sitio['FechaContratacion'])) : 'N/A'; ?></td>
                                                <td><?php echo $sitio['FechaProximaRenovacion'] ? date("d/m/Y", strtotime($sitio['FechaProximaRenovacion'])) : 'N/A'; ?></td>
                                                <td>
                                                    <?php if (strtolower($sitio['EstadoServicio']) === 'activo'): ?>
                                                        <a href="gestionar_sitio_detalle.php?id=<?php echo $sitio['SitioID']; ?>" class="btn btn-xs btn-outline">Gestionar</a>
                                                        <a href="config_ftp.php?id=<?php echo $sitio['SitioID']; ?>" class="btn btn-xs btn-outline">FTP</a>
                                                    <?php elseif (strtolower($sitio['EstadoServicio']) === 'pendiente_pago'): ?>
                                                        <a href="facturas.php" class="btn btn-xs btn-warning">Pagar Factura</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
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
