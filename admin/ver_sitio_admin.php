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

$sitio_detalle = null;
$cliente_propietario = null;
$error_message_view_sitio = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_sitio_list'] = "ID de sitio no válido.";
    header("Location: gestionar_sitios_admin.php");
    exit();
}
$sitioID_ver = (int)$_GET['id'];

$sql_sitio = "SELECT sw.SitioID, sw.ClienteID, sw.PlanHostingID, sw.SubdominioElegido, sw.DominioCompleto, 
                     sw.EstadoServicio, sw.FechaContratacion, sw.FechaProximaRenovacion,
                     c.Nombre AS ClienteNombre, c.Apellidos AS ClienteApellidos, c.Email AS ClienteEmail,
                     ph.NombrePlan AS PlanNombre
              FROM SitioWeb sw
              JOIN Cliente c ON sw.ClienteID = c.ClienteID
              JOIN Plan_Hosting ph ON sw.PlanHostingID = ph.PlanHostingID
              WHERE sw.SitioID = ?";
$stmt_sitio = $conn->prepare($sql_sitio);

if ($stmt_sitio) {
    $stmt_sitio->bind_param("i", $sitioID_ver);
    $stmt_sitio->execute();
    $result_sitio = $stmt_sitio->get_result();
    if ($result_sitio->num_rows === 1) {
        $sitio_detalle = $result_sitio->fetch_assoc();
    } else {
        $_SESSION['error_message_sitio_list'] = "Sitio web no encontrado.";
        $stmt_sitio->close();
        $conn->close();
        header("Location: gestionar_sitios_admin.php");
        exit();
    }
    $stmt_sitio->close();
} else {
    $error_message_view_sitio = "Error al preparar la consulta del sitio: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Sitio Web #<?php echo htmlspecialchars($sitioID_ver); ?> - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;}
        .detail-item { background-color: var(--bg-color-secondary); padding: 15px; border-radius: var(--border-radius); border-left: 3px solid var(--primary-color); }
        .detail-item strong { display: block; font-weight: 600; color: var(--text-color); margin-bottom: 3px; font-size: 0.85rem; text-transform: uppercase; opacity: 0.8; }
        .detail-item span, .detail-item a { font-size: 1rem; color: var(--text-color-muted); text-decoration:none; }
        .detail-item a:hover { text-decoration:underline; color: var(--primary-color); }
        .site-actions-admin { margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); }
        .site-actions-admin .btn { margin-right: 10px; margin-bottom: 10px;}
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Detalle del Sitio Web</h1>
                    <?php if ($sitio_detalle): ?>
                    <p>Información de <strong><?php echo htmlspecialchars($sitio_detalle['DominioCompleto']); ?></strong> (ID: <?php echo htmlspecialchars($sitio_detalle['SitioID']); ?>)</p>
                    <?php endif; ?>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php if (isset($_SESSION['success_message_sitio_detalle'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message_sitio_detalle']) . '</div>';
                        unset($_SESSION['success_message_sitio_detalle']);
                    }?>
                    <?php if (isset($_SESSION['error_message_sitio_detalle'])) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message_sitio_detalle']) . '</div>';
                        unset($_SESSION['error_message_sitio_detalle']);
                    }?>
                    <?php if ($error_message_view_sitio): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message_view_sitio); ?></div>
                    <?php elseif ($sitio_detalle): ?>
                    <section class="content-section">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 class="section-subtitle" style="margin-bottom: 0; border-bottom: none;">Información del Sitio</h2>
                            <div>
                            </div>
                        </div>
                        <div class="details-grid">
                            <div class="detail-item"><strong>ID Sitio:</strong> <span><?php echo htmlspecialchars($sitio_detalle['SitioID']); ?></span></div>
                            <div class="detail-item"><strong>Dominio Completo:</strong> <span><?php echo htmlspecialchars($sitio_detalle['DominioCompleto']); ?></span></div>
                            <div class="detail-item"><strong>Subdominio Elegido:</strong> <span><?php echo htmlspecialchars($sitio_detalle['SubdominioElegido']); ?></span></div>
                            <div class="detail-item"><strong>Plan Contratado:</strong> <span><?php echo htmlspecialchars($sitio_detalle['PlanNombre']); ?> (ID: <?php echo htmlspecialchars($sitio_detalle['PlanHostingID']); ?>)</span></div>
                            <div class="detail-item"><strong>Estado del Servicio:</strong> 
                                <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', htmlspecialchars($sitio_detalle['EstadoServicio']))); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($sitio_detalle['EstadoServicio']))); ?>
                                </span>
                            </div>
                            <div class="detail-item"><strong>Fecha Contratación:</strong> <span><?php echo date("d/m/Y H:i", strtotime($sitio_detalle['FechaContratacion'])); ?></span></div>
                            <div class="detail-item"><strong>Próxima Renovación:</strong> <span><?php echo $sitio_detalle['FechaProximaRenovacion'] ? date("d/m/Y", strtotime($sitio_detalle['FechaProximaRenovacion'])) : 'N/A'; ?></span></div>
                        </div>
                         <div class="details-grid" style="margin-top:0;">
                             <div class="detail-item"><strong>Cliente Propietario:</strong> 
                                <span>
                                    <a href="ver_usuario_admin.php?id=<?php echo $sitio_detalle['ClienteID']; ?>">
                                        <?php echo htmlspecialchars($sitio_detalle['ClienteNombre'] . ' ' . $sitio_detalle['ClienteApellidos']); ?> (ID: <?php echo $sitio_detalle['ClienteID']; ?>)
                                    </a>
                                </span>
                            </div>
                            <div class="detail-item"><strong>Email Cliente:</strong> <span><?php echo htmlspecialchars($sitio_detalle['ClienteEmail']); ?></span></div>
                        </div>

                        <div class="site-actions-admin">
                            <h3 class="section-subtitle" style="font-size:1.2rem; margin-bottom:15px; border-bottom:none;">Acciones Administrativas</h3>
                            <form action="cambiar_estado_sitio.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="sitio_id" value="<?php echo $sitio_detalle['SitioID']; ?>">
                                <select name="nuevo_estado" style="padding: 8px; border-radius:var(--border-radius); border:1px solid var(--border-color); margin-right:5px;">
                                    <option value="activo" <?php echo (strtolower($sitio_detalle['EstadoServicio']) == 'activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="suspendido" <?php echo (strtolower($sitio_detalle['EstadoServicio']) == 'suspendido') ? 'selected' : ''; ?>>Suspendido</option>
                                    <option value="cancelado" <?php echo (strtolower($sitio_detalle['EstadoServicio']) == 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                                    <option value="pendiente_pago" <?php echo (strtolower($sitio_detalle['EstadoServicio']) == 'pendiente_pago') ? 'selected' : ''; ?>>Pendiente de Pago</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Cambiar Estado</button>
                            </form>
                            <a href="gestionar_archivos_sitio.php?id=<?php echo $sitio_detalle['SitioID']; ?>" class="btn btn-outline">Gestionar Archivos (FTP)</a>
                            <a href="ver_facturas_sitio.php?sitio_id=<?php echo $sitio_detalle['SitioID']; ?>" class="btn btn-outline">Ver Facturas del Sitio</a>
                            <form action="eliminar_sitio_admin.php" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás SEGURO de que quieres eliminar este sitio web (<?php echo htmlspecialchars($sitio_detalle['DominioCompleto']); ?>)? Esta acción es irreversible y borrará todos sus datos.');">
                                <input type="hidden" name="sitio_id_eliminar" value="<?php echo $sitio_detalle['SitioID']; ?>">
                                <button type="submit" class="btn btn-danger">Eliminar Sitio</button>
                            </form>
                        </div>
                    </section>
                    <div style="margin-top: 30px;">
                        <a href="gestionar_sitios_admin.php" class="btn btn-outline">&larr; Volver al Listado de Sitios</a>
                    </div>
                    <?php else: ?>
                         <div class="empty-state"><p>No se pudo cargar la información del sitio web.</p></div>
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
<?php
if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
