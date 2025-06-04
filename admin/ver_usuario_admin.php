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

$usuario_detalle = null;
$sitios_usuario = [];
$facturas_usuario = [];
$error_message_view = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_user_list'] = "ID de usuario no válido.";
    header("Location: gestionar_usuarios.php");
    exit();
}
$clienteID_ver = (int)$_GET['id'];

$stmt_user = $conn->prepare("SELECT ClienteID, Nombre, Apellidos, Email, Telefono, Pais, Direccion, Fecha_Registro FROM Cliente WHERE ClienteID = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $clienteID_ver);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows === 1) {
        $usuario_detalle = $result_user->fetch_assoc();

        $stmt_sites = $conn->prepare("SELECT SitioID, DominioCompleto, EstadoServicio, FechaContratacion FROM SitioWeb WHERE ClienteID = ? ORDER BY FechaContratacion DESC");
        if($stmt_sites){
            $stmt_sites->bind_param("i", $clienteID_ver);
            $stmt_sites->execute();
            $result_sites = $stmt_sites->get_result();
            while($row_site = $result_sites->fetch_assoc()){
                $sitios_usuario[] = $row_site;
            }
            $stmt_sites->close();
        }

        $stmt_invoices = $conn->prepare("SELECT FacturaID, Descripcion, Monto, Estado, FechaEmision FROM Factura WHERE ClienteID = ? ORDER BY FechaEmision DESC LIMIT 5");
        if($stmt_invoices){
            $stmt_invoices->bind_param("i", $clienteID_ver);
            $stmt_invoices->execute();
            $result_invoices = $stmt_invoices->get_result();
            while($row_invoice = $result_invoices->fetch_assoc()){
                $facturas_usuario[] = $row_invoice;
            }
            $stmt_invoices->close();
        }

    } else {
        $_SESSION['error_message_user_list'] = "Usuario no encontrado.";
        $stmt_user->close();
        $conn->close();
        header("Location: gestionar_usuarios.php");
        exit();
    }
    $stmt_user->close();
} else {
    $error_message_view = "Error al preparar la consulta del usuario: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Usuario #<?php echo htmlspecialchars($clienteID_ver); ?> - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .profile-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;}
        .profile-detail-item { background-color: var(--bg-color-secondary); padding: 15px; border-radius: var(--border-radius); border-left: 3px solid var(--primary-color); }
        .profile-detail-item strong { display: block; font-weight: 600; color: var(--text-color); margin-bottom: 3px; font-size: 0.85rem; text-transform: uppercase; opacity: 0.8; }
        .profile-detail-item span { font-size: 1rem; color: var(--text-color-muted); }
        .related-info-section h3 { font-size: 1.2rem; margin-bottom:15px; color: var(--text-color); padding-bottom: 8px; border-bottom: 1px solid var(--border-color);}
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Detalle del Usuario</h1>
                    <?php if ($usuario_detalle): ?>
                    <p>Información de <?php echo htmlspecialchars($usuario_detalle['Nombre'] . ' ' . $usuario_detalle['Apellidos']); ?> (ID: <?php echo htmlspecialchars($usuario_detalle['ClienteID']); ?>)</p>
                    <?php endif; ?>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php if ($error_message_view): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message_view); ?></div>
                    <?php elseif ($usuario_detalle): ?>
                    <section class="content-section">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 class="section-subtitle" style="margin-bottom: 0; border-bottom: none;">Información Personal</h2>
                            <a href="editar_usuario_admin.php?id=<?php echo $usuario_detalle['ClienteID']; ?>" class="btn btn-primary">Editar Usuario</a>
                        </div>
                        <div class="profile-details-grid">
                            <div class="profile-detail-item"><strong>Nombre:</strong> <span><?php echo htmlspecialchars($usuario_detalle['Nombre']); ?></span></div>
                            <div class="profile-detail-item"><strong>Apellidos:</strong> <span><?php echo htmlspecialchars($usuario_detalle['Apellidos']); ?></span></div>
                            <div class="profile-detail-item"><strong>Email:</strong> <span><?php echo htmlspecialchars($usuario_detalle['Email']); ?></span></div>
                            <div class="profile-detail-item"><strong>Teléfono:</strong> <span><?php echo htmlspecialchars($usuario_detalle['Telefono'] ?? 'N/A'); ?></span></div>
                            <div class="profile-detail-item"><strong>País:</strong> <span><?php echo htmlspecialchars($usuario_detalle['Pais'] ?? 'N/A'); ?></span></div>
                            <div class="profile-detail-item"><strong>Dirección:</strong> <span><?php echo htmlspecialchars($usuario_detalle['Direccion'] ?? 'N/A'); ?></span></div>
                            <div class="profile-detail-item"><strong>Fecha Registro:</strong> <span><?php echo date("d/m/Y H:i", strtotime($usuario_detalle['Fecha_Registro'])); ?></span></div>
                        </div>
                    </section>

                    <section class="content-section related-info-section">
                        <h3>Sitios Web Asociados</h3>
                        <?php if (empty($sitios_usuario)): ?>
                            <p>Este usuario no tiene sitios web registrados.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead><tr><th>ID Sitio</th><th>Dominio</th><th>Estado</th><th>Contratado</th><th>Acciones</th></tr></thead>
                                    <tbody>
                                    <?php foreach($sitios_usuario as $sitio): ?>
                                        <tr>
                                            <td><?php echo $sitio['SitioID']; ?></td>
                                            <td><?php echo htmlspecialchars($sitio['DominioCompleto']); ?></td>
                                            <td><span class="status-badge status-<?php echo strtolower(str_replace('_', '-', htmlspecialchars($sitio['EstadoServicio']))); ?>"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($sitio['EstadoServicio']))); ?></span></td>
                                            <td><?php echo date("d/m/Y", strtotime($sitio['FechaContratacion'])); ?></td>
                                            <td><a href="ver_sitio_admin.php?id=<?php echo $sitio['SitioID']; ?>" class="btn btn-xs btn-outline">Gestionar Sitio</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="content-section related-info-section">
                        <h3>Últimas Facturas</h3>
                         <?php if (empty($facturas_usuario)): ?>
                            <p>Este usuario no tiene facturas.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead><tr><th>ID Factura</th><th>Descripción</th><th>Monto</th><th>Estado</th><th>Fecha Emisión</th><th>Acciones</th></tr></thead>
                                    <tbody>
                                    <?php foreach($facturas_usuario as $factura): ?>
                                        <tr>
                                            <td>#<?php echo $factura['FacturaID']; ?></td>
                                            <td><?php echo htmlspecialchars($factura['Descripcion']); ?></td>
                                            <td><?php echo number_format($factura['Monto'], 2, ',', '.'); ?> €</td>
                                            <td><span class="status-badge status-<?php echo strtolower(htmlspecialchars($factura['Estado'])); ?>"><?php echo ucfirst(htmlspecialchars($factura['Estado'])); ?></span></td>
                                            <td><?php echo date("d/m/Y", strtotime($factura['FechaEmision'])); ?></td>
                                            <td><a href="ver_factura_admin.php?id=<?php echo $factura['FacturaID']; ?>" class="btn btn-xs btn-outline">Ver Factura</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if(count($facturas_usuario) >= 5): ?>
                                <p style="text-align:right; margin-top:10px;"><a href="gestionar_facturas_admin.php?cliente_id=<?php echo $clienteID_ver; ?>">Ver todas las facturas de este usuario &rarr;</a></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                    <div style="margin-top: 30px;">
                        <a href="gestionar_usuarios.php" class="btn btn-outline">&larr; Volver al Listado de Usuarios</a>
                    </div>
                    <?php else: ?>
                         <div class="empty-state"><p>No se pudo cargar la información del usuario.</p></div>
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
