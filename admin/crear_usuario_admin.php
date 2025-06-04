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

$success_message = null;
$error_message = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["Nombre"] ?? '');
    $apellidos = trim($_POST["Apellidos"] ?? '');
    $email = trim(filter_var($_POST["Email"] ?? '', FILTER_SANITIZE_EMAIL));
    $passwd = $_POST["Passwd"] ?? '';
    $telefono = trim($_POST["Telefono"] ?? '');
    $pais = $_POST["Pais"] ?? '';
    $direccion = trim($_POST["Direccion"] ?? '');

    if (empty($nombre) || empty($apellidos) || empty($email) || empty($passwd) || empty($pais)) {
        $error_message = "Nombre, Apellidos, Email, Contraseña y País son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "El formato del email no es válido.";
    } elseif (strlen($passwd) < 8) {
        $error_message = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        $stmt_check_email = $conn->prepare("SELECT ClienteID FROM Cliente WHERE Email = ?");
        if ($stmt_check_email) {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $result_check_email = $stmt_check_email->get_result();

            if ($result_check_email->num_rows > 0) {
                $error_message = "Este email ya está registrado.";
            } else {
                $passwdHashed = password_hash($passwd, PASSWORD_DEFAULT);
                $sql_insert_cliente = "INSERT INTO Cliente (Nombre, Apellidos, Email, Passwd, Telefono, Pais, Direccion, Fecha_Registro) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt_insert = $conn->prepare($sql_insert_cliente);
                if ($stmt_insert) {
                    $stmt_insert->bind_param("sssssss", $nombre, $apellidos, $email, $passwdHashed, $telefono, $pais, $direccion);
                    if ($stmt_insert->execute()) {
                        $_SESSION['success_message_user_list'] = "Usuario '" . htmlspecialchars($nombre . " " . $apellidos) . "' creado exitosamente.";
                        $stmt_insert->close();
                        $conn->close();
                        header("Location: gestionar_usuarios.php");
                        exit();
                    } else {
                        $error_message = "Error al crear el usuario: " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                } else {
                     $error_message = "Error al preparar la inserción: " . $conn->error;
                }
            }
            $stmt_check_email->close();
        } else {
            $error_message = "Error al verificar el email: " . $conn->error;
        }
    }
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
    <title>Crear Nuevo Usuario - Admin k8servers</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Crear Nuevo Usuario</h1>
                    <p>Añade una nueva cuenta de cliente al sistema.</p>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section form-wrapper" style="max-width: 700px; margin: 0 auto;">
                        <h2 class="section-subtitle form-title" style="text-align:center;">Datos del Nuevo Usuario</h2>
                        <?php
                        if (!empty($success_message)) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                        }
                        if (!empty($error_message)) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                        }
                        ?>
                        <form action="crear_usuario_admin.php" method="POST" class="styled-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="Nombre">Nombre</label>
                                    <input type="text" id="Nombre" name="Nombre" value="<?php echo htmlspecialchars($_POST['Nombre'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="Apellidos">Apellidos</label>
                                    <input type="text" id="Apellidos" name="Apellidos" value="<?php echo htmlspecialchars($_POST['Apellidos'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Email">Email</label>
                                <input type="email" id="Email" name="Email" value="<?php echo htmlspecialchars($_POST['Email'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="Passwd">Contraseña</label>
                                <input type="password" id="Passwd" name="Passwd" required minlength="8">
                                <small>Mínimo 8 caracteres.</small>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="Telefono">Teléfono (Opcional)</label>
                                    <input type="tel" id="Telefono" name="Telefono" value="<?php echo htmlspecialchars($_POST['Telefono'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="Pais">País</label>
                                    <select id="Pais" name="Pais" required>
                                        <option value="">Seleccionar País...</option>
                                        <option value="ES" <?php echo (($_POST['Pais'] ?? '') == 'ES') ? 'selected' : ''; ?>>España</option>
                                        <option value="PT" <?php echo (($_POST['Pais'] ?? '') == 'PT') ? 'selected' : ''; ?>>Portugal</option>
                                        <option value="FR" <?php echo (($_POST['Pais'] ?? '') == 'FR') ? 'selected' : ''; ?>>Francia</option>
                                        <option value="AND" <?php echo (($_POST['Pais'] ?? '') == 'AND') ? 'selected' : ''; ?>>Andorra</option>
                                        <option value="OTRO" <?php echo (($_POST['Pais'] ?? '') == 'OTRO') ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Direccion">Dirección (Opcional)</label>
                                <textarea id="Direccion" name="Direccion" rows="3"><?php echo htmlspecialchars($_POST['Direccion'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-lg">Crear Usuario</button>
                                <a href="gestionar_usuarios.php" class="btn btn-outline" style="margin-left:15px;">Cancelar</a>
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
