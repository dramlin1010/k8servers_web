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

$usuario_editar = null;
$success_message_edit = null;
$error_message_edit = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_user_list'] = "ID de usuario no válido para editar.";
    header("Location: gestionar_usuarios.php");
    exit();
}
$clienteID_editar = (int)$_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["Nombre"] ?? '');
    $apellidos = trim($_POST["Apellidos"] ?? '');
    $email_post = trim(filter_var($_POST["Email"] ?? '', FILTER_SANITIZE_EMAIL));
    $telefono = trim($_POST["Telefono"] ?? '');
    $pais = $_POST["Pais"] ?? '';
    $direccion = trim($_POST["Direccion"] ?? '');
    $nueva_passwd = $_POST["NuevaPasswd"] ?? '';

    if (empty($nombre) || empty($apellidos) || empty($email_post) || empty($pais)) {
        $error_message_edit = "Nombre, Apellidos, Email y País son obligatorios.";
    } elseif (!filter_var($email_post, FILTER_VALIDATE_EMAIL)) {
        $error_message_edit = "El formato del email no es válido.";
    } else {
        $stmt_check_email = $conn->prepare("SELECT ClienteID FROM Cliente WHERE Email = ? AND ClienteID != ?");
        if($stmt_check_email){
            $stmt_check_email->bind_param("si", $email_post, $clienteID_editar);
            $stmt_check_email->execute();
            $result_check_email = $stmt_check_email->get_result();
            if ($result_check_email->num_rows > 0) {
                $error_message_edit = "El email '" . htmlspecialchars($email_post) . "' ya está en uso por otro usuario.";
            }
            $stmt_check_email->close();
        } else {
            $error_message_edit = "Error verificando email: " . $conn->error;
        }

        if (empty($error_message_edit)) {
            $params_sql = [];
            $types_sql = "";
            $sql_update_parts = [];

            $sql_update_parts[] = "Nombre = ?"; $params_sql[] = $nombre; $types_sql .= "s";
            $sql_update_parts[] = "Apellidos = ?"; $params_sql[] = $apellidos; $types_sql .= "s";
            $sql_update_parts[] = "Email = ?"; $params_sql[] = $email_post; $types_sql .= "s";
            $sql_update_parts[] = "Telefono = ?"; $params_sql[] = $telefono; $types_sql .= "s";
            $sql_update_parts[] = "Pais = ?"; $params_sql[] = $pais; $types_sql .= "s";
            $sql_update_parts[] = "Direccion = ?"; $params_sql[] = $direccion; $types_sql .= "s";

            if (!empty($nueva_passwd)) {
                if (strlen($nueva_passwd) < 8) {
                    $error_message_edit = "La nueva contraseña debe tener al menos 8 caracteres.";
                } else {
                    $passwdHashed = password_hash($nueva_passwd, PASSWORD_DEFAULT);
                    $sql_update_parts[] = "Passwd = ?"; $params_sql[] = $passwdHashed; $types_sql .= "s";
                }
            }
            
            if (empty($error_message_edit)) {
                $params_sql[] = $clienteID_editar; $types_sql .= "i";
                $sql_update = "UPDATE Cliente SET " . implode(", ", $sql_update_parts) . " WHERE ClienteID = ?";
                
                $stmt_update = $conn->prepare($sql_update);
                if ($stmt_update) {
                    $stmt_update->bind_param($types_sql, ...$params_sql);
                    if ($stmt_update->execute()) {
                        $_SESSION['success_message_user_list'] = "Usuario ID #" . $clienteID_editar . " actualizado exitosamente.";
                        $stmt_update->close();
                        $conn->close();
                        header("Location: gestionar_usuarios.php");
                        exit();
                    } else {
                        $error_message_edit = "Error al actualizar el usuario: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                } else {
                    $error_message_edit = "Error al preparar la actualización: " . $conn->error;
                }
            }
        }
    }
}

$stmt_fetch = $conn->prepare("SELECT ClienteID, Nombre, Apellidos, Email, Telefono, Pais, Direccion FROM Cliente WHERE ClienteID = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $clienteID_editar);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($result_fetch->num_rows === 1) {
        $usuario_editar = $result_fetch->fetch_assoc();
    } else {
        $_SESSION['error_message_user_list'] = "Usuario no encontrado para editar.";
        $stmt_fetch->close();
        $conn->close();
        header("Location: gestionar_usuarios.php");
        exit();
    }
    $stmt_fetch->close();
} else {
    $error_message_edit = "Error al cargar datos del usuario: " . $conn->error;
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario #<?php echo htmlspecialchars($clienteID_editar); ?> - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Editar Usuario</h1>
                    <?php if ($usuario_editar): ?>
                    <p>Modificando datos de <?php echo htmlspecialchars($usuario_editar['Nombre'] . ' ' . $usuario_editar['Apellidos']); ?></p>
                    <?php endif; ?>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section form-wrapper" style="max-width: 700px; margin: 0 auto;">
                        <h2 class="section-subtitle form-title" style="text-align:center;">Información del Usuario</h2>
                        <?php
                        if (!empty($success_message_edit)) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($success_message_edit) . '</div>';
                        }
                        if (!empty($error_message_edit)) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_edit) . '</div>';
                        }
                        ?>
                        <?php if ($usuario_editar): ?>
                        <form action="editar_usuario_admin.php?id=<?php echo $clienteID_editar; ?>" method="POST" class="styled-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="Nombre">Nombre</label>
                                    <input type="text" id="Nombre" name="Nombre" value="<?php echo htmlspecialchars($_POST['Nombre'] ?? $usuario_editar['Nombre']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="Apellidos">Apellidos</label>
                                    <input type="text" id="Apellidos" name="Apellidos" value="<?php echo htmlspecialchars($_POST['Apellidos'] ?? $usuario_editar['Apellidos']); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Email">Email</label>
                                <input type="email" id="Email" name="Email" value="<?php echo htmlspecialchars($_POST['Email'] ?? $usuario_editar['Email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="NuevaPasswd">Nueva Contraseña (Dejar en blanco para no cambiar)</label>
                                <input type="password" id="NuevaPasswd" name="NuevaPasswd" minlength="8">
                                <small>Si se ingresa, debe tener al menos 8 caracteres.</small>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="Telefono">Teléfono</label>
                                    <input type="tel" id="Telefono" name="Telefono" value="<?php echo htmlspecialchars($_POST['Telefono'] ?? $usuario_editar['Telefono'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="Pais">País</label>
                                    <select id="Pais" name="Pais" required>
                                        <option value="ES" <?php echo (($_POST['Pais'] ?? $usuario_editar['Pais'] ?? '') == 'ES') ? 'selected' : ''; ?>>España</option>
                                        <option value="PT" <?php echo (($_POST['Pais'] ?? $usuario_editar['Pais'] ?? '') == 'PT') ? 'selected' : ''; ?>>Portugal</option>
                                        <option value="FR" <?php echo (($_POST['Pais'] ?? $usuario_editar['Pais'] ?? '') == 'FR') ? 'selected' : ''; ?>>Francia</option>
                                        <option value="AND" <?php echo (($_POST['Pais'] ?? $usuario_editar['Pais'] ?? '') == 'AND') ? 'selected' : ''; ?>>Andorra</option>
                                        <option value="OTRO" <?php echo (!in_array(($_POST['Pais'] ?? $usuario_editar['Pais'] ?? ''), ['ES', 'PT', 'FR', 'AND']) && !empty(($_POST['Pais'] ?? $usuario_editar['Pais'] ?? ''))) ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Direccion">Dirección</label>
                                <textarea id="Direccion" name="Direccion" rows="3"><?php echo htmlspecialchars($_POST['Direccion'] ?? $usuario_editar['Direccion'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-lg">Guardar Cambios</button>
                                <a href="gestionar_usuarios.php" class="btn btn-outline" style="margin-left:15px;">Cancelar</a>
                            </div>
                        </form>
                        <?php else: ?>
                            <p>No se pudo cargar la información del usuario para editar.</p>
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
