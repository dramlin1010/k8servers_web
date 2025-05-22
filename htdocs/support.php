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
$success_message_support = null;
$error_message_support = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $asunto = trim($_POST['asunto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $prioridad = $_POST['prioridad'] ?? 'media';
    $sitioID = isset($_POST['sitioID']) ? (int)$_POST['sitioID'] : null;
    
    $estado = 'abierto';
    $fechaCreacion = date('Y-m-d H:i:s');

    if (empty($asunto) || empty($descripcion) || empty($prioridad)) {
        $error_message_support = "Por favor, completa todos los campos obligatorios (Asunto, Descripción, Prioridad).";
    } else {
        $sql_insert_ticket = "INSERT INTO Ticket_Soporte (ClienteID, SitioID, FechaCreacion, Asunto, Estado, Prioridad) 
                              VALUES (?, ?, ?, ?, ?, ?)";

        $stmt_insert_ticket = $conn->prepare($sql_insert_ticket);
        if ($stmt_insert_ticket) {
            $sitioID_param = ($sitioID > 0) ? $sitioID : null;

            $stmt_insert_ticket->bind_param("iissss", $clienteID, $sitioID_param, $fechaCreacion, $asunto, $estado, $prioridad);

            if ($stmt_insert_ticket->execute()) {
                $nuevoTicketID = $conn->insert_id;

                $sql_insert_mensaje = "INSERT INTO Mensaje_Ticket (TicketID, UsuarioID, EsAdmin, Contenido, FechaEnvio)
                                       VALUES (?, ?, FALSE, ?, NOW())";
                $stmt_insert_mensaje = $conn->prepare($sql_insert_mensaje);
                if ($stmt_insert_mensaje) {
                    $stmt_insert_mensaje->bind_param("iis", $nuevoTicketID, $clienteID, $descripcion);
                    if ($stmt_insert_mensaje->execute()) {
                        $success_message_support = "Ticket enviado con éxito (ID: #$nuevoTicketID). Nuestro equipo se pondrá en contacto contigo pronto.";
                    } else {
                        $error_message_support = "Ticket creado, pero hubo un error al guardar la descripción: " . $stmt_insert_mensaje->error;
                    }
                    $stmt_insert_mensaje->close();
                } else {
                     $error_message_support = "Ticket creado, pero hubo un error al preparar la descripción: " . $conn->error;
                }
            } else {
                $error_message_support = "Error al enviar el ticket: " . $stmt_insert_ticket->error;
            }
            $stmt_insert_ticket->close();
        } else {
            $error_message_support = "Error al preparar la consulta del ticket: " . $conn->error;
        }
    }
}

$sitios_usuario = [];
$sql_sitios = "SELECT SitioID, DominioCompleto FROM SitioWeb WHERE ClienteID = ? AND EstadoServicio = 'activo'";
$stmt_sitios = $conn->prepare($sql_sitios);
if ($stmt_sitios) {
    $stmt_sitios->bind_param("i", $clienteID);
    $stmt_sitios->execute();
    $result_sitios = $stmt_sitios->get_result();
    if ($result_sitios->num_rows > 0) {
        while ($row = $result_sitios->fetch_assoc()) {
            $sitios_usuario[] = $row;
        }
    }
    $stmt_sitios->close();
} else {
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte Técnico - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Tickets de Soporte</h1>
                    <p>Crea un nuevo ticket o revisa el estado de tus solicitudes existentes.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <div style="margin-bottom: 30px; text-align: right;">
                        <a href="ver_tickets.php" class="btn btn-outline">Ver Mis Tickets Anteriores</a>
                    </div>

                    <section class="content-section form-wrapper" style="max-width: 700px; margin: 0 auto;">
                        <h2 class="section-subtitle form-title" style="text-align:center;">Crear Nuevo Ticket de Soporte</h2>
                        
                        <?php
                        if (!empty($success_message_support)) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($success_message_support) . '</div>';
                        }
                        if (!empty($error_message_support)) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_support) . '</div>';
                        }
                        ?>

                        <form action="support.php" method="POST" class="styled-form">
                            <div class="form-group">
                                <label for="asunto">Asunto del Ticket</label>
                                <input type="text" id="asunto" name="asunto" required placeholder="Ej: Problema con FTP, Consulta sobre facturación">
                            </div>

                            <div class="form-group">
                                <label for="sitioID">Sitio Web Relacionado (Opcional)</label>
                                <select id="sitioID" name="sitioID">
                                    <option value="">-- General / No aplica a un sitio específico --</option>
                                    <?php foreach ($sitios_usuario as $sitio): ?>
                                        <option value="<?php echo $sitio['SitioID']; ?>"><?php echo htmlspecialchars($sitio['DominioCompleto']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small>Si tu consulta está relacionada con un sitio web específico, selecciónalo aquí.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="prioridad">Prioridad</label>
                                <select id="prioridad" name="prioridad" required>
                                    <option value="media">Media (Respuesta estándar)</option>
                                    <option value="alta">Alta (Problema urgente)</option>
                                    <option value="baja">Baja (Consulta general)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Describe tu Problema o Consulta</label>
                                <textarea id="descripcion" name="descripcion" rows="8" required placeholder="Por favor, proporciona todos los detalles posibles para ayudarnos a resolver tu solicitud rápidamente. Incluye pasos para reproducir el problema si aplica."></textarea>
                            </div>

                            <div class="form-actions" style="justify-content: center;">
                                <button type="submit" class="btn btn-primary btn-lg">Enviar Ticket</button>
                            </div>
                        </form>
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
<?php
if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
