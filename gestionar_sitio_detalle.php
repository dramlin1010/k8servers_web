<?php
require_once 'config.php';
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

$clienteID_session = $_SESSION['ClienteID'];
$sitio_detalle = null;
$error_message_detalle = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_sitios'] = "ID de sitio no válido o no especificado.";
    header("Location: mis_sitios.php");
    exit();
}
$sitioID_gestion = (int)$_GET['id'];

$sql_sitio = "SELECT sw.SitioID, sw.DominioCompleto, sw.EstadoServicio, sw.EstadoAprovisionamientoK8S,
                     sw.FechaContratacion, sw.FechaProximaRenovacion, sw.DirectorioEFSRuta,
                     ph.NombrePlan, ph.Descripcion AS PlanDescripcion, ph.Precio AS PlanPrecio
              FROM SitioWeb sw
              JOIN Plan_Hosting ph ON sw.PlanHostingID = ph.PlanHostingID
              WHERE sw.SitioID = ? AND sw.ClienteID = ?";
$stmt_sitio = $conn->prepare($sql_sitio);

if ($stmt_sitio) {
    $stmt_sitio->bind_param("ii", $sitioID_gestion, $clienteID_session);
    $stmt_sitio->execute();
    $result_sitio = $stmt_sitio->get_result();

    if ($result_sitio->num_rows === 1) {
        $sitio_detalle = $result_sitio->fetch_assoc();
    } else {
        $_SESSION['error_message_sitios'] = "Sitio no encontrado o no tienes permiso para gestionarlo.";
        $stmt_sitio->close();
        $conn->close();
        header("Location: mis_sitios.php");
        exit();
    }
    $stmt_sitio->close();
} else {
    // Guardar el error para mostrarlo en la página o redirigir
    $_SESSION['error_message_sitios'] = "Error al preparar la consulta del sitio: " . $conn->error;
    $conn->close();
    header("Location: mis_sitios.php");
    exit();
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Sitio: <?php echo htmlspecialchars($sitio_detalle['DominioCompleto'] ?? 'N/A'); ?> - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .site-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .detail-card {
            background-color: var(--card-bg-secondary);
            padding: 20px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
        }
        .detail-card h3 {
            font-size: 0.9rem;
            text-transform: uppercase;
            color: var(--text-color-muted);
            margin-top: 0;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .detail-card p {
            font-size: 1.1rem;
            color: var(--text-color);
            margin: 0;
            word-break: break-word;
        }
        .management-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .action-card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--box-shadow-current);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
        }
        .action-card .icon-big {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        .action-card h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        .action-card p {
            font-size: 0.9rem;
            color: var(--text-color-muted);
            margin-bottom: 20px;
            min-height: 40px;
        }
        .status-aprovisionamiento-k8s-no-iniciado,
        .status-aprovisionamiento-k8s-creacion-directorio-pendiente,
        .status-aprovisionamiento-k8s-directorio-creado,
        .status-aprovisionamiento-k8s-despliegue-k8s-pendiente,
        .status-aprovisionamiento-k8s-desplegando-k8s,
        .status-aprovisionamiento-k8s-error-aprovisionamiento {
            font-weight: bold;
        }
        .status-aprovisionamiento-k8s-k8s-aprovisionado { color: var(--success-color); font-weight: bold; }
        .status-aprovisionamiento-k8s-error-aprovisionamiento { color: var(--accent-color); }
        .status-aprovisionamiento-k8s-creacion-directorio-pendiente,
        .status-aprovisionamiento-k8s-despliegue-k8s-pendiente { color: var(--warning-color); }

    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Gestionar Sitio Web</h1>
                    <p>Detalles y opciones para: <strong><?php echo htmlspecialchars($sitio_detalle['DominioCompleto'] ?? 'N/A'); ?></strong></p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php if ($error_message_detalle): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message_detalle); ?></div>
                    <?php endif; ?>

                    <?php if ($sitio_detalle): ?>
                    <section class="content-section">
                        <h2 class="section-subtitle">Información General del Sitio</h2>
                        <div class="site-details-grid">
                            <div class="detail-card">
                                <h3>Dominio Principal</h3>
                                <p><?php echo htmlspecialchars($sitio_detalle['DominioCompleto']); ?></p>
                            </div>
                            <div class="detail-card">
                                <h3>Plan Contratado</h3>
                                <p><?php echo htmlspecialchars($sitio_detalle['NombrePlan']); ?></p>
                            </div>
                            <div class="detail-card">
                                <h3>Estado del Servicio</h3>
                                <p><span class="status-badge status-<?php echo strtolower(str_replace('_', '-', htmlspecialchars($sitio_detalle['EstadoServicio']))); ?>"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($sitio_detalle['EstadoServicio']))); ?></span></p>
                            </div>
                            <div class="detail-card">
                                <h3>Estado de Aprovisionamiento</h3>
                                <p><span class="status-aprovisionamiento-k8s-<?php echo strtolower(str_replace('_', '-', htmlspecialchars($sitio_detalle['EstadoAprovisionamientoK8S']))); ?>"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($sitio_detalle['EstadoAprovisionamientoK8S']))); ?></span></p>
                            </div>
                            <div class="detail-card">
                                <h3>Fecha de Contratación</h3>
                                <p><?php echo $sitio_detalle['FechaContratacion'] ? date("d/m/Y", strtotime($sitio_detalle['FechaContratacion'])) : 'N/A'; ?></p>
                            </div>
                            <div class="detail-card">
                                <h3>Próxima Renovación</h3>
                                <p><?php echo $sitio_detalle['FechaProximaRenovacion'] ? date("d/m/Y", strtotime($sitio_detalle['FechaProximaRenovacion'])) : 'N/A'; ?></p>
                            </div>
                             <div class="detail-card">
                                <h3>Directorio Raíz (EFS)</h3>
                                <p><code><?php echo htmlspecialchars($sitio_detalle['DirectorioEFSRuta'] ? $sitio_detalle['DirectorioEFSRuta'].'/www' : 'No asignado aún'); ?></code></p>
                            </div>
                        </div>
                    </section>

                    <section class="content-section">
                        <h2 class="section-subtitle">Herramientas de Gestión</h2>
                        <div class="management-actions-grid">
                            <div class="action-card">
                                <div class="icon-big">&#128187;</div>
                                <h4>Gestor de Archivos</h4>
                                <p>Sube, descarga y administra los archivos de tu sitio web.</p>
                                <?php if ($sitio_detalle['EstadoServicio'] === 'activo' && $sitio_detalle['EstadoAprovisionamientoK8S'] === 'k8s_aprovisionado'): ?>
                                    <a href="gestor_archivos.php?sitio_id=<?php echo $sitio_detalle['SitioID']; ?>" class="btn btn-primary">Acceder al Gestor</a>
                                <?php else: ?>
                                    <button class="btn btn-primary" disabled title="El sitio debe estar activo y aprovisionado">Acceder al Gestor</button>
                                <?php endif; ?>
                            </div>
                            <div class="action-card">
                                <div class="icon-big">&#128273;</div>
                                <h4>Credenciales FTP/SFTP</h4>
                                <p>Información para conectar con tu cliente FTP/SFTP favorito.</p>
                                <button class="btn btn-outline" disabled>Ver Credenciales (Próximamente)</button>
                            </div>
                            <div class="action-card">
                                <div class="icon-big">&#128179;</div>
                                <h4>Bases de Datos</h4>
                                <p>Crea y gestiona tus bases de datos MySQL.</p>
                                <button class="btn btn-outline" disabled>Gestionar BD (Próximamente)</button>
                            </div>
                            <div class="action-card">
                                <div class="icon-big">&#127760;</div>
                                <h4>Dominios y DNS</h4>
                                <p>Apunta dominios personalizados y gestiona zonas DNS.</p>
                                <button class="btn btn-outline" disabled>Gestionar Dominios (Próximamente)</button>
                            </div>
                            <div class="action-card">
                                <div class="icon-big">&#128190;</div>
                                <h4>Copias de Seguridad</h4>
                                <p>Administra y restaura las copias de seguridad de tu sitio.</p>
                                <button class="btn btn-outline" disabled>Ver Backups (Próximamente)</button>
                            </div>
                             <div class="action-card">
                                <div class="icon-big">&#128200;</div>
                                <h4>Estadísticas</h4>
                                <p>Consulta el uso de recursos y tráfico de tu sitio web.</p>
                                <button class="btn btn-outline" disabled>Ver Estadísticas (Próximamente)</button>
                            </div>
                        </div>
                    </section>
                    <div style="margin-top: 30px;">
                        <a href="mis_sitios.php" class="btn btn-outline">&larr; Volver a Mis Sitios</a>
                    </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No se pudo cargar la información del sitio. Por favor, inténtalo de nuevo.</p>
                            <a href="mis_sitios.php" class="btn btn-primary">Volver a Mis Sitios</a>
                        </div>
                    <?php endif; ?>
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
