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
$usuario_perfil = null;

$sql = "SELECT Nombre, Apellidos, Email, Telefono, Pais, Direccion, Fecha_Registro 
        FROM Cliente 
        WHERE ClienteID = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $clienteID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario_perfil = $result->fetch_assoc();
    } else {
        session_destroy();
        setcookie("session_token", "", time() - 3600, "/");
        $_SESSION['error_message'] = "No se pudo encontrar la información de tu perfil. Por favor, inicia sesión de nuevo.";
        header('Location: login.php');
        exit();
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Error al cargar la información del perfil.";
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .profile-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .profile-detail-item {
            background-color: var(--bg-color-secondary);
            padding: 20px;
            border-radius: var(--border-radius);
            border-left: 3px solid var(--primary-color);
        }
        .profile-detail-item strong {
            display: block;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
            font-size: 0.9rem;
            text-transform: uppercase;
            opacity: 0.8;
        }
        .profile-detail-item span {
            font-size: 1.05rem;
            color: var(--text-color-muted);
        }
        .profile-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Mi Perfil</h1>
                    <p>Consulta y gestiona la información de tu cuenta.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php
                    if (isset($_SESSION['success_message'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                        unset($_SESSION['success_message']);
                    }
                    if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                        unset($_SESSION['error_message']);
                    }
                    ?>
                    <section class="content-section">
                        <h2 class="section-subtitle">Información de la Cuenta</h2>
                        <?php if ($usuario_perfil): ?>
                            <div class="profile-details-grid">
                                <div class="profile-detail-item">
                                    <strong>Nombre Completo</strong>
                                    <span><?php echo htmlspecialchars($usuario_perfil['Nombre'] . ' ' . $usuario_perfil['Apellidos']); ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <strong>Correo Electrónico</strong>
                                    <span><?php echo htmlspecialchars($usuario_perfil['Email']); ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <strong>Teléfono</strong>
                                    <span><?php echo $usuario_perfil['Telefono'] ? htmlspecialchars($usuario_perfil['Telefono']) : 'No proporcionado'; ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <strong>País</strong>
                                    <span><?php echo $usuario_perfil['Pais'] ? htmlspecialchars($usuario_perfil['Pais']) : 'No proporcionado'; ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <strong>Dirección</strong>
                                    <span><?php echo $usuario_perfil['Direccion'] ? htmlspecialchars($usuario_perfil['Direccion']) : 'No proporcionada'; ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <strong>Miembro Desde</strong>
                                    <span><?php echo $usuario_perfil['Fecha_Registro'] ? date("d/m/Y", strtotime($usuario_perfil['Fecha_Registro'])) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="profile-actions">
                                <a href="editar_perfil.php" class="btn btn-primary">Editar Perfil</a>
                                <a href="cambiar_contrasena.php" class="btn btn-outline" style="margin-left: 10px;">Cambiar Contraseña</a>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="icon-big">&#128100;</i>
                                <p>No se pudo cargar la información de tu perfil.</p>
                                <?php if(empty($_SESSION['error_message'])): ?>
                                <p>Por favor, intenta recargar la página o contacta con soporte si el problema persiste.</p>
                                <?php endif; ?>
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
