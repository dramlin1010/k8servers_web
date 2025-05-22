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

$clienteID_session = $_SESSION['ClienteID'];
$ticket_detalle = null;
$mensajes_ticket = [];
$error_message_detalle = null;
$success_message_detalle = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_ticket_list'] = "ID de ticket no válido.";
    header("Location: ver_tickets.php");
    exit();
}
$ticketID_ver = (int)$_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['respuesta_cliente'])) {
    $contenido_respuesta = trim($_POST['respuesta_cliente']);

    if (empty($contenido_respuesta)) {
        $error_message_detalle = "La respuesta no puede estar vacía.";
    } else {
        $sql_check_owner = "SELECT ClienteID, Estado FROM Ticket_Soporte WHERE TicketID = ?";
        $stmt_check = $conn->prepare($sql_check_owner);
        $stmt_check->bind_param("i", $ticketID_ver);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows === 1) {
            $ticket_owner_data = $result_check->fetch_assoc();
            if ((int)$ticket_owner_data['ClienteID'] !== (int)$clienteID_session) {
                $error_message_detalle = "No tienes permiso para responder a este ticket.";
            } else {
                $conn->begin_transaction();
                try {
                    $sql_insert_respuesta = "INSERT INTO Mensaje_Ticket (TicketID, UsuarioID, EsAdmin, Contenido, FechaEnvio) 
                                             VALUES (?, ?, FALSE, ?, NOW())";
                    $stmt_insert = $conn->prepare($sql_insert_respuesta);
                    $stmt_insert->bind_param("iis", $ticketID_ver, $clienteID_session, $contenido_respuesta);

                    if (!$stmt_insert->execute()) {
                        throw new Exception("Error al enviar la respuesta: " . $stmt_insert->error);
                    }
                    $stmt_insert->close();

                    $nuevo_estado_ticket = 'abierto';
                    if ($ticket_owner_data['Estado'] === 'esperando_cliente' || $ticket_owner_data['Estado'] === 'resuelto') {
                        $nuevo_estado_ticket = 'abierto';
                    } else if ($ticket_owner_data['Estado'] === 'cerrado') {
                        $nuevo_estado_ticket = 'abierto';
                    } else {
                        $nuevo_estado_ticket = $ticket_owner_data['Estado'];
                    }


                    $sql_update_ticket = "UPDATE Ticket_Soporte SET UltimaActualizacion = NOW(), Estado = ? WHERE TicketID = ?";
                    $stmt_update = $conn->prepare($sql_update_ticket);
                    $stmt_update->bind_param("si", $nuevo_estado_ticket, $ticketID_ver);

                    if (!$stmt_update->execute()) {
                        throw new Exception("Error al actualizar el ticket: " . $stmt_update->error);
                    }
                    $stmt_update->close();

                    $conn->commit();
                    $success_message_detalle = "Tu respuesta ha sido enviada.";
                    header("Location: ver_ticket_detalle.php?id=" . $ticketID_ver . "&respuesta_enviada=1");
                    exit();

                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message_detalle = "Error al procesar tu respuesta: " . $e->getMessage();
                }
            }
        } else {
            $error_message_detalle = "Ticket no encontrado.";
        }
        $stmt_check->close();
    }
}

if (isset($_GET['respuesta_enviada'])) {
    $success_message_detalle = "Tu respuesta ha sido enviada correctamente.";
}


$sql_ticket = "SELECT t.TicketID, t.Asunto, t.Estado, t.Prioridad, t.FechaCreacion, t.UltimaActualizacion,
                      s.DominioCompleto AS SitioDominio
               FROM Ticket_Soporte t
               LEFT JOIN SitioWeb s ON t.SitioID = s.SitioID
               WHERE t.TicketID = ? AND t.ClienteID = ?";
$stmt_ticket = $conn->prepare($sql_ticket);

if ($stmt_ticket) {
    $stmt_ticket->bind_param("ii", $ticketID_ver, $clienteID_session);
    $stmt_ticket->execute();
    $result_ticket = $stmt_ticket->get_result();

    if ($result_ticket->num_rows === 1) {
        $ticket_detalle = $result_ticket->fetch_assoc();

        $sql_mensajes = "SELECT mt.Contenido, mt.FechaEnvio, mt.EsAdmin, 
                                c.Nombre AS MsgClienteNombre, c.Apellidos AS MsgClienteApellidos
                         FROM Mensaje_Ticket mt
                         LEFT JOIN Cliente c ON mt.UsuarioID = c.ClienteID AND mt.EsAdmin = FALSE
                         WHERE mt.TicketID = ? 
                         ORDER BY mt.FechaEnvio ASC";
        $stmt_mensajes = $conn->prepare($sql_mensajes);
        if ($stmt_mensajes) {
            $stmt_mensajes->bind_param("i", $ticketID_ver);
            $stmt_mensajes->execute();
            $result_mensajes = $stmt_mensajes->get_result();
            while($row_msg = $result_mensajes->fetch_assoc()){
                $mensajes_ticket[] = $row_msg;
            }
            $stmt_mensajes->close();
        } else {
            $error_message_detalle = "Error al cargar mensajes del ticket: " . $conn->error;
        }
    } else {
        $_SESSION['error_message_ticket_list'] = "Ticket no encontrado o no tienes permiso para verlo.";
        $stmt_ticket->close();
        $conn->close();
        header("Location: ver_tickets.php");
        exit();
    }
    $stmt_ticket->close();
} else {
    $error_message_detalle = "Error al preparar la consulta del ticket: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Ticket #<?php echo htmlspecialchars((string)($ticketID_ver ?? '')); ?> - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .ticket-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 15px; margin-bottom: 25px;}
        .ticket-detail-item { background-color: var(--bg-color-secondary); padding: 12px 15px; border-radius: var(--border-radius); }
        .ticket-detail-item strong { display: block; font-weight: 600; color: var(--text-color); margin-bottom: 2px; font-size: 0.8rem; text-transform: uppercase; opacity: 0.7; }
        .ticket-detail-item span { font-size: 0.95rem; color: var(--text-color-muted); word-break: break-word; }

        .ticket-conversation { margin-top: 30px; }
        .message-item {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            position: relative;
        }
        .message-item.admin-reply {
            background-color: color-mix(in srgb, var(--primary-color) 10%, transparent);
            border-left: 4px solid var(--primary-color);
        }
        .message-item.client-message {
            background-color: var(--bg-color-secondary);
            border-left: 4px solid var(--accent-color);
        }
        .message-header {
            font-size: 0.85rem;
            color: var(--text-color-muted);
            margin-bottom: 8px;
        }
        .message-header strong { color: var(--text-color); font-weight: 600; }
        .message-content { color: var(--text-color); }
        .message-content p { margin: 0 0 10px 0; line-height: 1.6; }
        .message-content p:last-child { margin-bottom: 0; }

        .reply-form-section { margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color); }

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
                    <h1>Detalle de Ticket</h1>
                    <?php if ($ticket_detalle): ?>
                    <p>Ticket #<?php echo htmlspecialchars((string)($ticket_detalle['TicketID'] ?? 'N/A')); ?>: "<?php echo htmlspecialchars((string)($ticket_detalle['Asunto'] ?? 'Sin asunto')); ?>"</p>
                    <?php endif; ?>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php
                    if (!empty($success_message_detalle)) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($success_message_detalle) . '</div>';
                    }
                    if (!empty($error_message_detalle) && !$ticket_detalle) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_detalle) . '</div>';
                    } elseif (!empty($error_message_detalle) && $ticket_detalle) {
                         echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_detalle) . '</div>';
                    }
                    ?>

                    <?php if ($ticket_detalle):
                        $ticketID_display = htmlspecialchars((string)($ticket_detalle['TicketID'] ?? 'N/A'));
                        $asunto_display = htmlspecialchars((string)($ticket_detalle['Asunto'] ?? 'N/A'));
                        $sitioDominio_display = htmlspecialchars((string)($ticket_detalle['SitioDominio'] ?? 'N/A'));

                        $prioridad_display = ucfirst(htmlspecialchars((string)($ticket_detalle['Prioridad'] ?? 'N/A')));
                        $prioridad_class = strtolower(htmlspecialchars((string)($ticket_detalle['Prioridad'] ?? '')));

                        $estado_display = ucfirst(str_replace('_', ' ', htmlspecialchars((string)($ticket_detalle['Estado'] ?? 'N/A'))));
                        $estado_class = strtolower(str_replace('_', '-', htmlspecialchars((string)($ticket_detalle['Estado'] ?? ''))));

                        $fechaCreacion_display = isset($ticket_detalle['FechaCreacion']) ? date("d/m/Y H:i", strtotime($ticket_detalle['FechaCreacion'])) : 'N/A';
                        $ultimaActualizacion_display = isset($ticket_detalle['UltimaActualizacion']) ? date("d/m/Y H:i", strtotime($ticket_detalle['UltimaActualizacion'])) : 'N/A';
                    ?>
                    <section class="content-section">
                        <h2 class="section-subtitle">Información del Ticket</h2>
                        <div class="ticket-details-grid">
                            <div class="ticket-detail-item"><strong>ID Ticket:</strong> <span>#<?php echo $ticketID_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Asunto:</strong> <span><?php echo $asunto_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Sitio Relacionado:</strong> <span><?php echo $sitioDominio_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Prioridad:</strong> <span class="status-badge status-prioridad-<?php echo $prioridad_class; ?>"><?php echo $prioridad_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Estado:</strong> <span class="status-badge status-<?php echo $estado_class; ?>"><?php echo $estado_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Fecha Creación:</strong> <span><?php echo $fechaCreacion_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Última Actualización:</strong> <span><?php echo $ultimaActualizacion_display; ?></span></div>
                        </div>

                        <div class="ticket-conversation">
                            <h3 class="section-subtitle" style="font-size:1.2rem; margin-bottom:15px;">Conversación</h3>
                            <?php if (empty($mensajes_ticket)): ?>
                                <p class="text-center" style="color: var(--text-color-muted);">Aún no hay mensajes en esta conversación. Sé el primero en escribir.</p>
                            <?php else: ?>
                                <?php foreach ($mensajes_ticket as $mensaje):
                                    $nombre_emisor = "Desconocido";
                                    if ($mensaje['EsAdmin']) {
                                        $nombre_emisor = 'Soporte k8servers';
                                    } elseif (isset($mensaje['MsgClienteNombre']) || isset($mensaje['MsgClienteApellidos'])) {
                                        $nombre_emisor = 'Tú';
                                    }
                                ?>
                                <div class="message-item <?php echo $mensaje['EsAdmin'] ? 'admin-reply' : 'client-message'; ?>">
                                    <div class="message-header">
                                        <strong><?php echo $nombre_emisor; ?></strong>
                                        respondió el <?php echo isset($mensaje['FechaEnvio']) ? date("d/m/Y H:i", strtotime($mensaje['FechaEnvio'])) : 'Fecha desconocida'; ?>
                                    </div>
                                    <div class="message-content">
                                        <?php echo nl2br(htmlspecialchars((string)($mensaje['Contenido'] ?? ''))); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php
                        $estadoActualTicket = $ticket_detalle['Estado'] ?? '';
                        if ($estadoActualTicket !== 'cerrado'):
                        ?>
                        <div class="reply-form-section">
                             <h3 class="section-subtitle" style="font-size:1.2rem; margin-bottom:15px;">Añadir Respuesta</h3>
                            <form action="ver_ticket_detalle.php?id=<?php echo $ticketID_ver; ?>" method="POST" class="styled-form">
                                <div class="form-group">
                                    <label for="respuesta_cliente">Tu Mensaje:</label>
                                    <textarea id="respuesta_cliente" name="respuesta_cliente" rows="7" required placeholder="Escribe tu mensaje aquí..."></textarea>
                                </div>
                                <div class="form-actions" style="justify-content: flex-start;">
                                    <button type="submit" class="btn btn-primary btn-lg">Enviar Mensaje</button>
                                </div>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="reply-form-section" style="text-align:center; padding: 20px; background-color: var(--bg-color-secondary); border-radius: var(--border-radius);">
                            <p style="color: var(--text-color-muted); margin-bottom:15px;">Este ticket está marcado como "<?php echo $estado_display; ?>".</p>
                            <p style="color: var(--text-color-muted);">Si necesitas reabrirlo o tienes una nueva consulta, por favor, <a href="support.php">crea un nuevo ticket</a>.</p>
                        </div>
                        <?php endif; ?>
                    </section>
                    <div style="margin-top: 30px;">
                        <a href="ver_tickets.php<?php echo $ticketID_ver ? '?highlight_ticket='.$ticketID_ver : ''; ?>" class="btn btn-outline">&larr; Volver al Listado de Tickets</a>
                    </div>
                    <?php else: ?>
                         <div class="empty-state">
                            <span class="icon-big" style="font-size: 4rem; opacity: 0.5;">😟</span>
                            <p>No se pudo cargar la información del ticket, no existe o no tienes permiso para verlo.</p>
                            <a href="ver_tickets.php" class="btn btn-primary">Volver al Listado</a>
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
        const conversation = document.querySelector('.ticket-conversation');
        if (conversation && conversation.scrollHeight > conversation.clientHeight) {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('respuesta_enviada')) {
                const replyForm = document.querySelector('.reply-form-section');
                if (replyForm) {
                    replyForm.scrollIntoView({ behavior: 'smooth', block: 'end' });
                } else {
                    conversation.scrollTop = conversation.scrollHeight;
                }
            }
        }
    });
</script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
}
?>
