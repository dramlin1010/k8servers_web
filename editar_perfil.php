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
$success_message_profile = null;
$error_message_profile = null;
$usuario_actual = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $pais = $_POST['pais'] ?? '';
    $direccion = trim($_POST['direccion'] ?? '');

    if (empty($nombre) || empty($apellidos) || empty($pais)) {
        $error_message_profile = "Nombre, Apellidos y País son campos obligatorios.";
        $stmt_fetch_err = $conn->prepare("SELECT Nombre, Apellidos, Email, Telefono, Pais, Direccion FROM Cliente WHERE ClienteID = ?");
        if ($stmt_fetch_err) {
            $stmt_fetch_err->bind_param("i", $clienteID);
            $stmt_fetch_err->execute();
            $result_fetch_err = $stmt_fetch_err->get_result();
            $usuario_actual = $result_fetch_err->fetch_assoc();
            $stmt_fetch_err->close();
        }
    } else {
        $sql_update = "UPDATE Cliente SET Nombre = ?, Apellidos = ?, Telefono = ?, Pais = ?, Direccion = ? WHERE ClienteID = ?";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            $stmt_update->bind_param("sssssi", $nombre, $apellidos, $telefono, $pais, $direccion, $clienteID);
            if ($stmt_update->execute()) {
                $_SESSION['success_message'] = "Perfil actualizado exitosamente.";
                $_SESSION['NombreCliente'] = $nombre;
                $stmt_update->close();
                $conn->close();
                header("Location: perfil.php");
                exit();
            } else {
                $error_message_profile = "Error al actualizar el perfil: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $error_message_profile = "Error al preparar la actualización del perfil: " . $conn->error;
        }
        if ($error_message_profile && !$usuario_actual) {
             $stmt_fetch_err_upd = $conn->prepare("SELECT Nombre, Apellidos, Email, Telefono, Pais, Direccion FROM Cliente WHERE ClienteID = ?");
            if ($stmt_fetch_err_upd) {
                $stmt_fetch_err_upd->bind_param("i", $clienteID);
                $stmt_fetch_err_upd->execute();
                $result_fetch_err_upd = $stmt_fetch_err_upd->get_result();
                $usuario_actual = $result_fetch_err_upd->fetch_assoc();
                $stmt_fetch_err_upd->close();
            }
        }
    }
} else {
    $stmt_fetch = $conn->prepare("SELECT Nombre, Apellidos, Email, Telefono, Pais, Direccion FROM Cliente WHERE ClienteID = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $clienteID);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            $usuario_actual = $result_fetch->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "No se pudo cargar tu perfil para editar.";
            $conn->close();
            header("Location: perfil.php");
            exit();
        }
        $stmt_fetch->close();
    } else {
        $_SESSION['error_message'] = "Error al preparar la carga del perfil: " . $conn->error;
        $conn->close();
        header("Location: perfil.php");
        exit();
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
    <title>Editar Perfil - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Editar Mi Perfil</h1>
                    <p>Actualiza tu información personal.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section form-wrapper" style="max-width: 700px; margin: 0 auto;">
                        <h2 class="section-subtitle form-title" style="text-align:center;">Información Personal</h2>
                        
                        <?php
                        if (!empty($success_message_profile)) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($success_message_profile) . '</div>';
                        }
                        if (!empty($error_message_profile)) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($error_message_profile) . '</div>';
                        }
                        if (isset($_SESSION['error_message']) && empty($error_message_profile)) {
                             echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                             unset($_SESSION['error_message']);
                        }
                        ?>

                        <?php if ($usuario_actual): ?>
                        <form action="editar_perfil.php" method="POST" class="styled-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario_actual['Nombre']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="apellidos">Apellidos</label>
                                    <input type="text" id="apellidos" name="apellidos" value="<?php echo htmlspecialchars($usuario_actual['Apellidos']); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Correo Electrónico (No editable)</label>
                                <input type="email" id="email" name="email_display" value="<?php echo htmlspecialchars($usuario_actual['Email']); ?>" readonly disabled style="background-color: var(--bg-color); cursor: not-allowed;">
                            </div>
                             <div class="form-row">
                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario_actual['Telefono'] ?? ''); ?>" placeholder="Ej: +34123456789">
                                </div>
                                <div class="form-group">
                                    <label for="pais">País</label>
                                    <select id="pais" name="pais" required>
                                        <option value="ES" <?php echo (($usuario_actual['Pais'] ?? '') == 'ES') ? 'selected' : ''; ?>>España</option>
                                        <option value="PT" <?php echo (($usuario_actual['Pais'] ?? '') == 'PT') ? 'selected' : ''; ?>>Portugal</option>
                                        <option value="FR" <?php echo (($usuario_actual['Pais'] ?? '') == 'FR') ? 'selected' : ''; ?>>Francia</option>
                                        <option value="AND" <?php echo (($usuario_actual['Pais'] ?? '') == 'AND') ? 'selected' : ''; ?>>Andorra</option>
                                        <option value="OTRO" <?php echo (!in_array(($usuario_actual['Pais'] ?? ''), ['ES', 'PT', 'FR', 'AND']) && !empty($usuario_actual['Pais'])) ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="direccion">Dirección</label>
                                <textarea id="direccion" name="direccion" rows="3"><?php echo htmlspecialchars($usuario_actual['Direccion'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-actions" style="justify-content: center;">
                                <button type="submit" class="btn btn-primary btn-lg">Guardar Cambios</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <p>No se pudo cargar la información del perfil para editar. Por favor, <a href="perfil.php">vuelve a tu perfil</a> e inténtalo de nuevo.</p>
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
