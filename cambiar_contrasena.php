<?php
require_once 'config.php';
session_start();
require 'conexion.php';

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    $_SESSION['error_message'] = "Acceso no autorizado.";
    header("Location: login.php");
    exit();
}

if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy();
    setcookie("session_token", "", time() - 3600, "/");
    $_SESSION['error_message'] = "Token de sesión inválido.";
    header("Location: login.php");
    exit();
}

$clienteID = $_SESSION['ClienteID'];
$success_message_password = null;
$error_message_password = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

    if (empty($contrasena_actual) || empty($nueva_contrasena) || empty($confirmar_contrasena)) {
        $error_message_password = "Todos los campos son obligatorios.";
    } elseif ($nueva_contrasena !== $confirmar_contrasena) {
        $error_message_password = "La nueva contraseña y la confirmación no coinciden.";
    } elseif (strlen($nueva_contrasena) < 8) {
        $error_message_password = "La nueva contraseña debe tener al menos 8 caracteres.";
    } else {
        $sql_check = "SELECT Passwd FROM Cliente WHERE ClienteID = ?";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("i", $clienteID);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $usuario_actual = $result_check->fetch_assoc();
            $stmt_check->close();

            if ($usuario_actual && password_verify($contrasena_actual, $usuario_actual['Passwd'])) {
                $nueva_contrasena_hashed = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
                $sql_update = "UPDATE Cliente SET Passwd = ? WHERE ClienteID = ?";
                $stmt_update = $conn->prepare($sql_update);
                if ($stmt_update) {
                    $stmt_update->bind_param("si", $nueva_contrasena_hashed, $clienteID);
                    if ($stmt_update->execute()) {
                        $success_message_password = "Contraseña actualizada exitosamente.";
                    } else {
                        $error_message_password = "Error al actualizar la contraseña: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                } else {
                    $error_message_password = "Error al preparar la actualización: " . $conn->error;
                }
            } else {
                $error_message_password = "La contraseña actual es incorrecta.";
            }
        } else {
            $error_message_password = "Error al verificar la contraseña actual: " . $conn->error;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Cambiar Contraseña</h1>
                    <p>Actualiza la contraseña de tu cuenta para mayor seguridad.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section form-wrapper" style="max-width: 600px; margin: 0 auto;">
                        <h2 class="section-subtitle form-title" style="text-align:center;">Actualizar Contraseña</h2>
                        
                        <?php
                        if (!empty($success_message_password)) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($success_message_password) . '</div>';
                        }
                        if (!empty($error_message_password)) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_password) . '</div>';
                        }
                        ?>

                        <form action="cambiar_contrasena.php" method="POST" class="styled-form">
                            <div class="form-group">
                                <label for="contrasena_actual">Contraseña Actual</label>
                                <input type="password" id="contrasena_actual" name="contrasena_actual" required>
                            </div>
                            <div class="form-group">
                                <label for="nueva_contrasena">Nueva Contraseña</label>
                                <input type="password" id="nueva_contrasena" name="nueva_contrasena" required minlength="8">
                                <small>Debe tener al menos 8 caracteres.</small>
                            </div>
                            <div class="form-group">
                                <label for="confirmar_contrasena">Confirmar Nueva Contraseña</label>
                                <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required minlength="8">
                            </div>
                            <div class="form-actions" style="justify-content: center;">
                                <button type="submit" class="btn btn-primary btn-lg">Actualizar Contraseña</button>
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
