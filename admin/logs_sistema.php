<?php
require_once '../config.php';
session_start();
require_once '../conexion.php';

if (
    !isset($_COOKIE['admin_session']) ||
    !isset($_SESSION['token']) ||
    $_COOKIE['admin_session'] !== session_id() ||
    $_SESSION['token'] !== $_COOKIE['session_token']
) {
    if (isset($_SESSION['token'])) {
        unset($_SESSION['token']);
    }
    if (isset($_COOKIE['session_token'])) {
        setcookie('session_token', '', time() - 3600, '/');
    }
    if (isset($_COOKIE['admin_session'])) {
        setcookie('admin_session', '', time() - 3600, '/');
    }
    session_destroy();
    header('Location: ../login.php?error=admin_auth_failed');
    exit();
}

$logs = [];
$error_message_logs = null;
$log_file_path = __DIR__ . '/../login_activity.json';

if (file_exists($log_file_path)) {
    $json_data = file_get_contents($log_file_path);
    if ($json_data !== false) {
        $decoded_data = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
            $logs = $decoded_data;
            // Ordenar logs por timestamp descendente (más recientes primero)
            usort($logs, function ($a, $b) {
                $timestamp_a = isset($a['timestamp'])
                    ? strtotime($a['timestamp'])
                    : 0;
                $timestamp_b = isset($b['timestamp'])
                    ? strtotime($b['timestamp'])
                    : 0;
                return $timestamp_b - $timestamp_a;
            });
        } else {
            $error_message_logs =
                'Error al decodificar el archivo de logs. Formato JSON inválido.';
        }
    } else {
        $error_message_logs = 'Error al leer el archivo de logs.';
    }
} else {
    $error_message_logs = 'El archivo de logs no existe.';
}

if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs del Sistema - Admin k8servers</title>
    <link rel="stylesheet" href="../css/styles.css"> {/* Ruta al CSS en la raíz */}
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_admin.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Logs del Sistema</h1>
                    <p>Registro de actividad de inicio de sesión de usuarios.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php if ($error_message_logs): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message_logs); ?>
                        </div>
                    <?php endif; ?>

                    <section class="content-section">
                        <h2 class="section-subtitle">Actividad de Inicio de Sesión</h2>
                        <?php if (empty($logs) && !$error_message_logs): ?>
                            <div class="empty-state">
                                <i class="icon-big">&#128220;</i>
                                <p>No hay registros de actividad en el sistema.</p>
                            </div>
                        <?php elseif (!empty($logs)): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Timestamp (UTC)</th>
                                            <th>Cliente ID</th>
                                            <th>Email</th>
                                            <th>Dirección IP</th>
                                            <th>Tipo de Evento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log_entry): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    if (
                                                        isset(
                                                            $log_entry[
                                                                'timestamp'
                                                            ]
                                                        )
                                                    ) {
                                                        try {
                                                            $date = new DateTime(
                                                                $log_entry[
                                                                    'timestamp'
                                                                ],
                                                                new DateTimeZone(
                                                                    'UTC'
                                                                )
                                                            );
                                                            echo htmlspecialchars(
                                                                $date->format(
                                                                    'd/m/Y H:i:s'
                                                                )
                                                            );
                                                        } catch (
                                                            Exception $e
                                                        ) {
                                                            echo htmlspecialchars(
                                                                $log_entry[
                                                                    'timestamp'
                                                                ]
                                                            ) .
                                                                ' (Formato Inválido)';
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo isset($log_entry['clienteId']) ? htmlspecialchars((string) $log_entry['clienteId']) : 'N/A'; ?></td>
                                                <td><?php echo isset($log_entry['email']) ? htmlspecialchars($log_entry['email']) : 'N/A'; ?></td>
                                                <td><?php echo isset($log_entry['ip']) ? htmlspecialchars($log_entry['ip']) : 'N/A'; ?></td>
                                                <td><?php echo isset($log_entry['type']) ? htmlspecialchars(ucfirst(str_replace('_', ' ', $log_entry['type']))) : 'N/A'; ?></td>
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