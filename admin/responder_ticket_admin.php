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

$ticket_detalle = null;
$mensajes_ticket = [];
$error_message_ticket_admin = null;
$success_message_ticket_admin = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_ticket_list_admin'] = "ID de ticket no válido.";
    header("Location: gestionar_tickets_admin.php");
    exit();
}
$ticketID_ver = (int)$_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['respuesta_admin'])) {
    $contenido_respuesta = trim($_POST['respuesta_admin']);
    $nuevo_estado_ticket = $_POST['nuevo_estado_ticket'] ?? null;

    if (empty($contenido_respuesta)) {
        $error_message_ticket_admin = "La respuesta no puede estar vacía.";
    } else {
        $conn->begin_transaction();
        try {
            $sql_insert_respuesta = "INSERT INTO Mensaje_Ticket (TicketID, UsuarioID, EsAdmin, Contenido, FechaEnvio) 
                                     VALUES (?, ?, TRUE, ?, NOW())";
            $stmt_insert_respuesta = $conn->prepare($sql_insert_respuesta);
            
            $admin_user_id_for_message = $_SESSION['AdminID'] ?? null;

            $stmt_insert_respuesta->bind_param("iis", $ticketID_ver, $admin_user_id_for_message, $contenido_respuesta);

            if (!$stmt_insert_respuesta->execute()) {
                throw new Exception("Error al enviar la respuesta: " . $stmt_insert_respuesta->error);
            }
            $stmt_insert_respuesta->close();

            $update_parts = ["UltimaActualizacion = NOW()"];
            $types = "";
            $params_update = [];

            if ($nuevo_estado_ticket && in_array($nuevo_estado_ticket, ['abierto', 'en_progreso', 'esperando_cliente', 'resuelto', 'cerrado'])) {
                $update_parts[] = "Estado = ?";
                $types .= "s";
                $params_update[] = $nuevo_estado_ticket;
            }
            $params_update[] = $ticketID_ver;
            $types .= "i";

            $sql_update_ticket = "UPDATE Ticket_Soporte SET " . implode(", ", $update_parts) . " WHERE TicketID = ?";
            $stmt_update_ticket = $conn->prepare($sql_update_ticket);
            $stmt_update_ticket->bind_param($types, ...$params_update);
            
            if (!$stmt_update_ticket->execute()) {
                 throw new Exception("Error al actualizar el ticket: " . $stmt_update_ticket->error);
            }
            $stmt_update_ticket->close();

            $conn->commit();
            $success_message_ticket_admin = "Respuesta enviada y ticket actualizado.";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message_ticket_admin = "Error: " . $e->getMessage();
        }
    }
}

$sql_ticket = "SELECT t.TicketID, t.ClienteID, t.SitioID, t.Asunto, t.Estado, t.Prioridad, t.FechaCreacion, t.UltimaActualizacion,
                      c.Nombre AS ClienteNombre, c.Apellidos AS ClienteApellidos, c.Email AS ClienteEmail,
                      s.DominioCompleto AS SitioDominio
               FROM Ticket_Soporte t
               JOIN Cliente c ON t.ClienteID = c.ClienteID
               LEFT JOIN SitioWeb s ON t.SitioID = s.SitioID
               WHERE t.TicketID = ?";
$stmt_ticket = $conn->prepare($sql_ticket);

if ($stmt_ticket) {
    $stmt_ticket->bind_param("i", $ticketID_ver);
    $stmt_ticket->execute();
    $result_ticket = $stmt_ticket->get_result();
    if ($result_ticket->num_rows === 1) {
        $ticket_detalle = $result_ticket->fetch_assoc();

        $sql_mensajes = "SELECT mt.Contenido, mt.FechaEnvio, mt.EsAdmin, mt.UsuarioID,
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
            $error_message_ticket_admin = "Error al cargar mensajes del ticket: " . $conn->error;
        }
    } else {
        $_SESSION['error_message_ticket_list_admin'] = "Ticket no encontrado o datos de cliente inaccesibles.";
        $stmt_ticket->close();
        if ($conn) $conn->close();
        header("Location: gestionar_tickets_admin.php");
        exit();
    }
    $stmt_ticket->close();
} else {
    $error_message_ticket_admin = "Error al preparar la consulta del ticket: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Ticket #<?php echo htmlspecialchars((string)($ticketID_ver ?? '')); ?> - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .ticket-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;}
        .ticket-detail-item { background-color: var(--bg-color-secondary); padding: 12px 15px; border-radius: var(--border-radius); }
        .ticket-detail-item strong { display: block; font-weight: 600; color: var(--text-color); margin-bottom: 2px; font-size: 0.8rem; text-transform: uppercase; opacity: 0.7; }
        .ticket-detail-item span, .ticket-detail-item a { font-size: 0.95rem; color: var(--text-color-muted); text-decoration:none; word-break: break-word; }
        .ticket-detail-item a:hover { text-decoration:underline; color: var(--primary-color); }

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
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Detalle y Respuesta de Ticket</h1>
                    <?php if ($ticket_detalle): ?>
                    <p>Ticket #<?php echo htmlspecialchars((string)($ticket_detalle['TicketID'] ?? 'N/A')); ?> - Asunto: "<?php echo htmlspecialchars((string)($ticket_detalle['Asunto'] ?? 'Sin asunto')); ?>"</p>
                    <?php endif; ?>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php
                    if (!empty($success_message_ticket_admin)) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($success_message_ticket_admin) . '</div>';
                    }
                    if (!empty($error_message_ticket_admin) && !$ticket_detalle) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_ticket_admin) . '</div>';
                    } elseif (!empty($error_message_ticket_admin) && $ticket_detalle) 
                         echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_ticket_admin) . '</div>';
                    }
                    ?>
                    <?php if ($ticket_detalle):
                        $ticketID_display = htmlspecialchars((string)($ticket_detalle['TicketID'] ?? 'N/A'));
                        $clienteID_raw = $ticket_detalle['ClienteID'] ?? null;
                        $clienteNombre_display = htmlspecialchars(trim(($ticket_detalle['ClienteNombre'] ?? '') . ' ' . ($ticket_detalle['ClienteApellidos'] ?? 'Cliente Desconocido')));
                        $clienteEmail_display = htmlspecialchars((string)($ticket_detalle['ClienteEmail'] ?? 'N/A'));
                        $clienteLink = $clienteID_raw ? "ver_usuario_admin.php?id=" . htmlspecialchars((string)$clienteID_raw) : "#";

                        $asunto_display = htmlspecialchars((string)($ticket_detalle['Asunto'] ?? 'N/A'));
                        $sitioID_raw = $ticket_detalle['SitioID'] ?? null;
                        $sitioDominio_display = htmlspecialchars((string)($ticket_detalle['SitioDominio'] ?? 'N/A'));
                        $sitioLink = ($sitioID_raw && $ticket_detalle['SitioDominio']) ? "ver_sitio_admin.php?id=" . htmlspecialchars((string)$sitioID_raw) : null;

                        $prioridad_display = ucfirst(htmlspecialchars((string)($ticket_detalle['Prioridad'] ?? 'N/A')));
                        $prioridad_class = strtolower(htmlspecialchars((string)($ticket_detalle['Prioridad'] ?? '')));

                        $estado_display = ucfirst(str_replace('_', ' ', htmlspecialchars((string)($ticket_detalle['Estado'] ?? 'N/A'))));
                        $estado_class = strtolower(str_replace('_', '-', htmlspecialchars((string)($ticket_detalle['Estado'] ?? ''))));
                        $estado_actual_form = htmlspecialchars((string)($ticket_detalle['Estado'] ?? ''));


                        $fechaCreacion_display = isset($ticket_detalle['FechaCreacion']) ? date("d/m/Y H:i", strtotime($ticket_detalle['FechaCreacion'])) : 'N/A';
                        $ultimaActualizacion_display = isset($ticket_detalle['UltimaActualizacion']) ? date("d/m/Y H:i", strtotime($ticket_detalle['UltimaActualizacion'])) : 'N/A';
                    ?>
                    <section class="content-section">
                        <h2 class="section-subtitle">Información del Ticket</h2>
                        <div class="ticket-details-grid">
                            <div class="ticket-detail-item"><strong>ID Ticket:</strong> <span>#<?php echo $ticketID_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Cliente:</strong> <span><a href="<?php echo $clienteLink; ?>"><?php echo $clienteNombre_display; ?></a> (<?php echo $clienteEmail_display; ?>)</span></div>
                            <div class="ticket-detail-item"><strong>Asunto:</strong> <span><?php echo $asunto_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Sitio Relacionado:</strong> <span><?php echo $sitioLink ? '<a href="'.$sitioLink.'">'.$sitioDominio_display.'</a>' : $sitioDominio_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Prioridad:</strong> <span class="status-badge status-prioridad-<?php echo $prioridad_class; ?>"><?php echo $prioridad_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Estado:</strong> <span class="status-badge status-<?php echo $estado_class; ?>"><?php echo $estado_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Fecha Creación:</strong> <span><?php echo $fechaCreacion_display; ?></span></div>
                            <div class="ticket-detail-item"><strong>Última Actualización:</strong> <span><?php echo $ultimaActualizacion_display; ?></span></div>
                        </div>

                        <div class="ticket-conversation">
                            <h3 class="section-subtitle" style="font-size:1.2rem; margin-bottom:15px;">Conversación</h3>
                            <?php if (empty($mensajes_ticket)): ?>
                                <p class="text-center" style="color: var(--text-color-muted);">No hay mensajes en este ticket aún.</p>
                            <?php else: ?>
                                <?php foreach ($mensajes_ticket as $mensaje):
                                    $nombre_emisor = "Desconocido";
                                    if ($mensaje['EsAdmin']) {
                                        $nombre_emisor = 'Soporte k8servers';
                                    } elseif (isset($mensaje['MsgClienteNombre']) || isset($mensaje['MsgClienteApellidos'])) {
                                        $nombre_emisor = htmlspecialchars(trim(($mensaje['MsgClienteNombre'] ?? '') . ' ' . ($mensaje['MsgClienteApellidos'] ?? '')));
                                    } elseif (isset($mensaje['UsuarioID'])) {
                                        $nombre_emisor = 'Cliente (ID: ' . htmlspecialchars((string)$mensaje['UsuarioID']) . ')';
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

                        <div class="reply-form-section">
                             <h3 class="section-subtitle" style="font-size:1.2rem; margin-bottom:15px;">Responder al Ticket</h3>
                            <form action="responder_ticket_admin.php?id=<?php echo $ticketID_ver; ?>" method="POST" class="styled-form">
                                <div class="form-group">
                                    <label for="respuesta_admin">Tu Respuesta:</label>
                                    <textarea id="respuesta_admin" name="respuesta_admin" rows="7" required placeholder="Escribe tu respuesta aquí..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="nuevo_estado_ticket">Cambiar Estado del Ticket (Opcional):</label>
                                    <select id="nuevo_estado_ticket" name="nuevo_estado_ticket">
                                        <option value="">-- Mantener Estado Actual (<?php echo $estado_display; ?>) --</option>
                                        <option value="abierto" <?php if($estado_actual_form === 'abierto') echo 'disabled'; ?>>Abierto</option>
                                        <option value="en_progreso" <?php if($estado_actual_form === 'en_progreso') echo 'disabled'; ?>>En Progreso</option>
                                        <option value="esperando_cliente" <?php if($estado_actual_form === 'esperando_cliente') echo 'disabled'; ?>>Esperando Respuesta del Cliente</option>
                                        <option value="resuelto" <?php if($estado_actual_form === 'resuelto') echo 'disabled'; ?>>Resuelto</option>
                                        <option value="cerrado" <?php if($estado_actual_form === 'cerrado') echo 'disabled'; ?>>Cerrado</option>
                                    </select>
                                </div>
                                <div class="form-actions" style="justify-content: flex-start;">
                                    <button type="submit" class="btn btn-primary btn-lg">Enviar Respuesta</button>
                                </div>
                            </form>
                        </div>
                    </section>
                    <div style="margin-top: 30px;">
                        <a href="gestionar_tickets_admin.php" class="btn btn-outline">&larr; Volver al Listado de Tickets</a>
                    </div>
                    <?php else: ?>
                         <div class="empty-state">
                            <span class="icon-big" style="font-size: 4rem; opacity: 0.5;">😟</span>
                            <p>No se pudo cargar la información del ticket o el ticket no existe.</p>
                            <a href="gestionar_tickets_admin.php" class="btn btn-primary">Volver al Listado</a>
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
<?php
if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
}
?>
