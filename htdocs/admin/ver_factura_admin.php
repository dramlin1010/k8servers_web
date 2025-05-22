<?php
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

$factura = null;
$error_message_view_factura_admin = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_factura_list_admin'] = "ID de factura no válido.";
    header("Location: gestionar_facturas_admin.php");
    exit();
}
$facturaID_ver = (int)$_GET['id'];

$sql_factura = "SELECT f.FacturaID, f.ClienteID, f.Descripcion, f.Monto, f.Estado, f.FechaEmision, f.FechaVencimiento, 
                       f.MetodoPago, f.TransaccionID, f.FechaPago,
                       c.Nombre AS ClienteNombre, c.Apellidos AS ClienteApellidos, c.Email AS ClienteEmail, 
                       c.Direccion AS ClienteDireccion, c.Pais AS ClientePais, c.Telefono AS ClienteTelefono,
                       s.SitioID AS SitioAsociadoID, s.DominioCompleto AS SitioDominio
                FROM Factura f
                JOIN Cliente c ON f.ClienteID = c.ClienteID
                LEFT JOIN SitioWeb s ON f.SitioID = s.SitioID
                WHERE f.FacturaID = ?";

$stmt = $conn->prepare($sql_factura);
if ($stmt) {
    $stmt->bind_param("i", $facturaID_ver);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $factura = $result->fetch_assoc();
    } else {
        $_SESSION['error_message_factura_list_admin'] = "Factura no encontrada.";
        $stmt->close();
        $conn->close();
        header("Location: gestionar_facturas_admin.php");
        exit();
    }
    $stmt->close();
} else {
    $error_message_view_factura_admin = "Error al preparar la consulta de la factura: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Factura #<?php echo htmlspecialchars($facturaID_ver); ?> - Admin k8servers</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .invoice-details-wrapper { max-width: 800px; margin: 0 auto; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; padding-bottom:20px; border-bottom: 1px solid var(--border-color); }
        .invoice-logo .panel-logo { font-size: 2rem; }
        .invoice-info p { margin-bottom: 5px; font-size:0.9rem; text-align: right; }
        .invoice-info strong { font-weight: 600; }
        .invoice-addresses { display: flex; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; }
        .invoice-addresses div { width: 100%; margin-bottom:20px; }
        @media (min-width: 600px) { .invoice-addresses div { width: 48%; margin-bottom:0; } }
        .invoice-addresses h3 { font-size: 1.1rem; margin-bottom: 10px; color: var(--text-color); border-bottom: 1px solid var(--border-color); padding-bottom: 5px;}
        .invoice-addresses p { margin-bottom: 3px; font-size: 0.95rem; color: var(--text-color-muted); }
        .invoice-items table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .invoice-items th, .invoice-items td { border: 1px solid var(--border-color); padding: 10px; text-align: left; font-size:0.95rem; }
        .invoice-items th { background-color: var(--bg-color-secondary); font-weight: 600; }
        .invoice-items td.amount, .invoice-items th.amount { text-align: right; }
        .invoice-total { text-align: right; margin-bottom: 30px; }
        .invoice-total p { margin-bottom: 8px; font-size: 1.1rem; }
        .invoice-total strong { font-size: 1.3rem; color: var(--primary-color); }
        .invoice-notes p { font-size: 0.9rem; color: var(--text-color-muted); }
        .invoice-print-action { text-align: center; margin-top: 30px; padding-top:20px; border-top: 1px solid var(--border-color); }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .panel-layout .panel-sidebar, .panel-main-content .panel-main-header, .invoice-print-action, #theme-toggle-panel, .print-hide { display: none !important; }
            .panel-layout .panel-main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .panel-content-area { padding: 20px !important; }
            .content-section { box-shadow: none !important; border: 1px solid #ccc !important; margin:0 !important; padding: 20px !important;}
            .invoice-details-wrapper { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header print-hide">
                <div class="container-fluid">
                    <h1>Detalle de Factura (Admin)</h1>
                    <p>Factura #<?php echo htmlspecialchars($facturaID_ver); ?></p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                     <?php if (isset($_SESSION['success_message_factura_admin'])) {
                        echo '<div class="alert alert-success print-hide">' . htmlspecialchars($_SESSION['success_message_factura_admin']) . '</div>';
                        unset($_SESSION['success_message_factura_admin']);
                    }?>
                    <?php if (isset($_SESSION['error_message_factura_admin'])) {
                        echo '<div class="alert alert-danger print-hide">' . htmlspecialchars($_SESSION['error_message_factura_admin']) . '</div>';
                        unset($_SESSION['error_message_factura_admin']);
                    }?>
                    <?php if ($error_message_view_factura_admin): ?>
                        <div class="alert alert-danger print-hide"><?php echo htmlspecialchars($error_message_view_factura_admin); ?></div>
                    <?php elseif ($factura): ?>
                    <section class="content-section">
                        <div class="invoice-details-wrapper">
                            <div class="invoice-header">
                                <div class="invoice-logo">
                                    <a href="../index.php" class="panel-logo">k8servers</a>
                                    <p style="font-size:0.9rem; color:var(--text-color-muted);">Tu Proveedor de Hosting Confiable</p>
                                </div>
                                <div class="invoice-info">
                                    <h2>FACTURA</h2>
                                    <p><strong>Nº Factura:</strong> #<?php echo htmlspecialchars($factura['FacturaID']); ?></p>
                                    <p><strong>Fecha Emisión:</strong> <?php echo date("d/m/Y", strtotime($factura['FechaEmision'])); ?></p>
                                    <?php if ($factura['FechaVencimiento']): ?>
                                    <p><strong>Fecha Vencimiento:</strong> <?php echo date("d/m/Y", strtotime($factura['FechaVencimiento'])); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Estado:</strong> <span class="status-badge status-<?php echo strtolower(htmlspecialchars($factura['Estado'])); ?>"><?php echo ucfirst(htmlspecialchars($factura['Estado'])); ?></span></p>
                                </div>
                            </div>

                            <div class="invoice-addresses">
                                <div>
                                    <h3>Facturado a:</h3>
                                    <p><strong><a href="ver_usuario_admin.php?id=<?php echo $factura['ClienteID']; ?>"><?php echo htmlspecialchars($factura['ClienteNombre'] . ' ' . $factura['ClienteApellidos']); ?></a></strong></p>
                                    <p><?php echo htmlspecialchars($factura['ClienteDireccion'] ?? 'N/A'); ?></p>
                                    <p><?php echo htmlspecialchars($factura['ClientePais'] ?? 'N/A'); ?></p>
                                    <p>Email: <?php echo htmlspecialchars($factura['ClienteEmail']); ?></p>
                                    <?php if ($factura['ClienteTelefono']): ?>
                                    <p>Tel: <?php echo htmlspecialchars($factura['ClienteTelefono']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3>Datos del Proveedor:</h3>
                                    <p><strong>k8servers</strong></p>
                                    <p>Avenida Larios 18001</p>
                                    <p>Granada, España</p>
                                    <p>Email: soporte@k8servers.es</p>
                                    <p>Tel: +34 123 456 789</p>
                                </div>
                            </div>

                            <div class="invoice-items">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Descripción</th>
                                            <th class="amount">Precio Unitario</th>
                                            <th class="amount">Cantidad</th>
                                            <th class="amount">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($factura['Descripcion']); ?>
                                                <?php if ($factura['SitioDominio']): ?>
                                                    <br><small style="color:var(--text-color-muted);">Servicio: <a href="ver_sitio_admin.php?id=<?php echo $factura['SitioAsociadoID']; ?>"><?php echo htmlspecialchars($factura['SitioDominio']); ?></a></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="amount"><?php echo number_format($factura['Monto'], 2, ',', '.'); ?> €</td>
                                            <td class="amount">1</td>
                                            <td class="amount"><?php echo number_format($factura['Monto'], 2, ',', '.'); ?> €</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="invoice-total">
                                <p>Subtotal: <?php echo number_format($factura['Monto'], 2, ',', '.'); ?> €</p>
                                <p>Impuestos (IVA 0% - Ejemplo): 0,00 €</p>
                                <p><strong>Total: <?php echo number_format($factura['Monto'], 2, ',', '.'); ?> €</strong></p>
                            </div>

                            <?php if (strtolower($factura['Estado']) === 'pagado' && $factura['FechaPago']): ?>
                            <div class="invoice-notes">
                                <p><strong>Pagado el:</strong> <?php echo date("d/m/Y H:i", strtotime($factura['FechaPago'])); ?></p>
                                <?php if ($factura['MetodoPago']): ?>
                                <p><strong>Método de Pago:</strong> <?php echo htmlspecialchars($factura['MetodoPago']); ?></p>
                                <?php endif; ?>
                                <?php if ($factura['TransaccionID']): ?>
                                <p><strong>ID Transacción:</strong> <?php echo htmlspecialchars($factura['TransaccionID']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="invoice-notes" style="margin-top:20px;">
                                <p>Gracias por su confianza en k8servers.</p>
                            </div>

                            <div class="invoice-print-action print-hide">
                                <button onclick="window.print();" class="btn btn-primary"><i class="icon" style="margin-right:5px;">&#128424;</i> Imprimir Factura</button>
                            </div>

                        </div>
                    </section>
                    <div style="margin-top: 30px;" class="print-hide">
                        <a href="gestionar_facturas_admin.php" class="btn btn-outline">&larr; Volver al Listado de Facturas</a>
                        <?php if ($factura && $factura['ClienteID']): ?>
                        <a href="ver_usuario_admin.php?id=<?php echo $factura['ClienteID']; ?>" class="btn btn-outline" style="margin-left:10px;">Ver Perfil del Cliente</a>
                        <?php endif; ?>
                         <?php if ($factura && $factura['SitioAsociadoID']): ?>
                        <a href="ver_sitio_admin.php?id=<?php echo $factura['SitioAsociadoID']; ?>" class="btn btn-outline" style="margin-left:10px;">Ver Sitio Asociado</a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                         <div class="empty-state print-hide"><p>No se pudo cargar la información de la factura.</p></div>
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
