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
$tickets = [];
$sql_tickets = "SELECT t.TicketID, t.Asunto, t.Estado, t.Prioridad, t.UltimaActualizacion, c.Email AS ClienteEmail
                FROM Ticket_Soporte t
                JOIN Cliente c ON t.ClienteID = c.ClienteID
                ORDER BY CASE t.Estado
                    WHEN 'abierto' THEN 1
                    WHEN 'en_progreso' THEN 2
                    WHEN 'esperando_cliente' THEN 3
                    ELSE 4 END, 
                CASE t.Prioridad
                    WHEN 'urgente' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'media' THEN 3
                    WHEN 'baja' THEN 4
                    ELSE 5 END,
                t.UltimaActualizacion DESC";
$result_tickets = $conn->query($sql_tickets);
if ($result_tickets && $result_tickets->num_rows > 0) {
    while($row = $result_tickets->fetch_assoc()) {
        $tickets[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Tickets de Soporte - Admin k8servers</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Gestionar Tickets de Soporte</h1>
                    <p>Atiende y gestiona las solicitudes de soporte de los clientes.</p>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section">
                        <h2 class="section-subtitle">Listado de Tickets</h2>
                        <?php if (empty($tickets)): ?>
                            <div class="empty-state"><p>No hay tickets de soporte activos o pendientes.</p></div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID Ticket</th>
                                            <th>Cliente Email</th>
                                            <th>Asunto</th>
                                            <th>Prioridad</th>
                                            <th>Estado</th>
                                            <th>Última Actualización</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($ticket['TicketID']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['ClienteEmail']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['Asunto']); ?></td>
                                            <td>
                                                <span class="status-badge status-prioridad-<?php echo strtolower(htmlspecialchars($ticket['Prioridad'])); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($ticket['Prioridad'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', htmlspecialchars($ticket['Estado']))); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($ticket['Estado']))); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date("d/m/Y H:i", strtotime($ticket['UltimaActualizacion'])); ?></td>
                                            <td>
                                                <a href="responder_ticket_admin.php?id=<?php echo $ticket['TicketID']; ?>" class="btn btn-xs btn-primary">Ver / Responder</a>
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
