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

require 'conexion.php';

$ClienteID = $_SESSION['ClienteID'];

$sql = "SELECT FacturaID, Descripcion, Estado, FechaVencimiento FROM Factura WHERE ClienteID = ? ORDER BY FechaVencimiento DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ClienteID);
$stmt->execute();
$result = $stmt->get_result();

$facturas = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facturas[] = $row;
    }
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Dashboard Principal</h1>
                    <p>Resumen de tu actividad y facturas recientes.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section">
                        <h2 class="section-subtitle">Mis Facturas Recientes</h2>
                        <?php if (empty($facturas)): ?>
                            <div class="empty-state">
                                <i class="icon-big">&#128179;</i>
                                <p>No tienes facturas pendientes ni historial de facturas.</p>
                                <p>Si deseas activar tu servicio de hosting, puedes hacerlo ahora.</p>
                                <a href="contratar_servicio.php" class="btn btn-primary">Contratar Hosting Ahora</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID Factura</th>
                                            <th>Descripción</th>
                                            <th>Estado</th>
                                            <th>Fecha de Vencimiento</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($facturas as $factura): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($factura['FacturaID']); ?></td>
                                                <td><?php echo htmlspecialchars($factura['Descripcion']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower(htmlspecialchars($factura['Estado'])); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($factura['Estado'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $factura['FechaVencimiento'] ? date("d/m/Y", strtotime($factura['FechaVencimiento'])) : 'N/A'; ?></td>
                                                <td>
                                                    <?php if (strtolower($factura['Estado']) === 'pendiente'): ?>
                                                        <form action="pagar_factura.php" method="POST" style="display:inline;">
                                                            <input type="hidden" name="factura_id" value="<?php echo $factura['FacturaID']; ?>">
                                                            <button class="btn btn-xs btn-primary" type="submit">Pagar</button>
                                                        </form>
                                                    <?php elseif (strtolower($factura['Estado']) === 'pagado'): ?>
                                                        <span class="btn btn-xs btn-success disabled">Pagado</span>
                                                    <?php else: ?>
                                                         <a href="ver_factura.php?id=<?php echo $factura['FacturaID']; ?>" class="btn btn-xs btn-outline">Ver</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                    
                    <section class="content-section">
                        <h2 class="section-subtitle">Accesos Rápidos</h2>
                        <div class="quick-actions-grid">
                            <a href="mis_sitios.php" class="quick-action-item">
                                <i class="icon-big">&#128187;</i>
                                <span>Gestionar Mis Sitios</span>
                            </a>
                            <a href="support.php" class="quick-action-item">
                                <i class="icon-big">&#9993;</i>
                                <span>Abrir Ticket de Soporte</span>
                            </a>
                            <a href="perfil.php" class="quick-action-item">
                                <i class="icon-big">&#128100;</i>
                                <span>Actualizar Mi Perfil</span>
                            </a>
                        </div>
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
