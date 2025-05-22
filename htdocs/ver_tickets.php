<?php
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

$clienteID = $_SESSION['ClienteID'];
$highlightTicketID = isset($_GET['highlight_ticket']) ? (int)$_GET['highlight_ticket'] : null;

$tickets = [];
$sql = "SELECT t.TicketID, t.Asunto, t.Estado, t.Prioridad, t.FechaCreacion, t.UltimaActualizacion, s.DominioCompleto
        FROM Ticket_Soporte t
        LEFT JOIN SitioWeb s ON t.SitioID = s.SitioID
        WHERE t.ClienteID = ? 
        ORDER BY CASE t.Estado
                    WHEN 'abierto' THEN 1
                    WHEN 'en_progreso' THEN 2
                    WHEN 'esperando_cliente' THEN 3
                    WHEN 'resuelto' THEN 4
                    WHEN 'cerrado' THEN 5
                    ELSE 6
                 END, t.UltimaActualizacion DESC, t.TicketID DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $clienteID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
    }
    $stmt->close();
} else {
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tickets de Soporte - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .status-badge.status-prioridad-alta { background-color: color-mix(in srgb, #e53935 25%, transparent); color: #e53935; }
        .dark-mode .status-badge.status-prioridad-alta { background-color: color-mix(in srgb, #ef5350 30%, transparent); color: #ef9a9a; }
        
        .status-badge.status-prioridad-media { background-color: color-mix(in srgb, #fb8c00 25%, transparent); color: #fb8c00; }
        .dark-mode .status-badge.status-prioridad-media { background-color: color-mix(in srgb, #ffa726 30%, transparent); color: #ffcc80; }

        .status-badge.status-prioridad-baja { background-color: color-mix(in srgb, #43a047 25%, transparent); color: #43a047; }
        .dark-mode .status-badge.status-prioridad-baja { background-color: color-mix(in srgb, #66bb6a 30%, transparent); color: #a5d6a7; }
        
        .status-badge.status-prioridad-urgente { background-color: color-mix(in srgb, #d32f2f 30%, transparent); color: #f44336; border: 1px solid #f44336; }
        .dark-mode .status-badge.status-prioridad-urgente { background-color: color-mix(in srgb, #e57373 35%, transparent); color: #ef5350; border: 1px solid #ef5350; }

        .status-badge.status-abierto { background-color: color-mix(in srgb, var(--primary-color) 20%, transparent); color: var(--primary-color); }
        .status-badge.status-en-progreso { background-color: color-mix(in srgb, #1e88e5 25%, transparent); color: #1e88e5; }
        .dark-mode .status-badge.status-en-progreso { background-color: color-mix(in srgb, #42a5f5 30%, transparent); color: #90caf9; }
        .status-badge.status-esperando-cliente { background-color: color-mix(in srgb, #fdd835 30%, transparent); color: #795548; }
        .dark-mode .status-badge.status-esperando-cliente { background-color: color-mix(in srgb, #fff176 35%, transparent); color: #5d4037; }
        .status-badge.status-resuelto { background-color: color-mix(in srgb, #4caf50 20%, transparent); color: #4caf50; }
        .dark-mode .status-badge.status-resuelto { background-color: color-mix(in srgb, #66bb6a 25%, transparent); color: #81c784; }
        .status-badge.status-cerrado { background-color: color-mix(in srgb, var(--text-color-muted) 20%, transparent); color: var(--text-color-muted); }
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Mis Tickets de Soporte</h1>
                    <p>Aquí puedes ver el historial y estado de tus solicitudes de soporte.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <div style="margin-bottom: 30px; text-align: right;">
                        <a href="support.php" class="btn btn-primary"><i class="icon" style="margin-right: 5px;">&#43;</i> Crear Nuevo Ticket</a>
                    </div>
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
                        <h2 class="section-subtitle">Historial de Tickets</h2>
                        <?php if (empty($tickets)): ?>
                            <div class="empty-state">
                                <i class="icon-big">&#9993;</i>
                                <p>Aún no has creado ningún ticket de soporte.</p>
                                <p>Si necesitas ayuda, no dudes en crear uno.</p>
                                <a href="support.php" class="btn btn-primary">Crear Ticket Ahora</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID Ticket</th>
                                            <th>Asunto</th>
                                            <th>Sitio Relacionado</th>
                                            <th>Prioridad</th>
                                            <th>Estado</th>
                                            <th>Última Actualización</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr class="<?php echo ($highlightTicketID === (int)$ticket['TicketID']) ? 'highlight-row' : ''; ?>">
                                                <td>#<?php echo htmlspecialchars($ticket['TicketID']); ?></td>
                                                <td><?php echo htmlspecialchars($ticket['Asunto']); ?></td>
                                                <td><?php echo $ticket['DominioCompleto'] ? htmlspecialchars($ticket['DominioCompleto']) : 'N/A'; ?></td>
                                                <td>
                                                    <span class="status-badge status-prioridad-<?php echo strtolower(htmlspecialchars($ticket['Prioridad'])); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($ticket['Prioridad'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-',str_replace('_', '-', htmlspecialchars($ticket['Estado'])))); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($ticket['Estado']))); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $ticket['UltimaActualizacion'] ? date("d/m/Y H:i", strtotime($ticket['UltimaActualizacion'])) : date("d/m/Y H:i", strtotime($ticket['FechaCreacion'])); ?></td>
                                                <td>
                                                    <a href="ver_ticket_detalle.php?id=<?php echo $ticket['TicketID']; ?>" class="btn btn-xs btn-outline">Ver / Responder</a>
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
