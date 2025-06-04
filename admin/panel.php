<?php
require_once '../config.php';

session_start();

if (!isset($_COOKIE['admin_session']) || !isset($_SESSION['token']) || $_COOKIE['admin_session'] !== session_id() || $_SESSION['token'] !== $_COOKIE['session_token']) {
    if (isset($_SESSION['token'])) unset($_SESSION['token']);
    if (isset($_COOKIE['session_token'])) setcookie("session_token", "", time() - 3600, "/");
    if (isset($_COOKIE['admin_session'])) setcookie("admin_session", "", time() - 3600, "/");
    session_destroy();
    header("Location: ../login.php?error=admin_auth_failed");
    exit();
}

require_once("../conexion.php");

$total_usuarios = 0;
$total_sitios_activos = 0;
$total_tickets_abiertos = 0;

$sql_usuarios = "SELECT COUNT(*) as total FROM Cliente";
$result_usuarios = $conn->query($sql_usuarios);
if ($result_usuarios && $result_usuarios->num_rows > 0) {
    $total_usuarios = $result_usuarios->fetch_assoc()['total'];
}

$sql_sitios = "SELECT COUNT(*) as total FROM SitioWeb WHERE EstadoServicio = 'activo'";
$result_sitios = $conn->query($sql_sitios);
if ($result_sitios && $result_sitios->num_rows > 0) {
    $total_sitios_activos = $result_sitios->fetch_assoc()['total'];
}

$sql_tickets = "SELECT COUNT(*) as total FROM Ticket_Soporte WHERE Estado = 'abierto' OR Estado = 'en_progreso'";
$result_tickets = $conn->query($sql_tickets);
if ($result_tickets && $result_tickets->num_rows > 0) {
    $total_tickets_abiertos = $result_tickets->fetch_assoc()['total'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - k8servers</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .dashboard-card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-current);
            text-align: center;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .dark-mode .dashboard-card:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.25);
        }
        .dashboard-card .card-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            display: block;
        }
        .dashboard-card h3 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 1.2rem;
            color: var(--text-color-muted);
            text-transform: uppercase;
            font-weight: 500;
        }
        .dashboard-card p.card-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }
        .dashboard-card a.card-link {
            display: inline-block;
            margin-top: 15px;
            font-size: 0.9rem;
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }
        .dashboard-card a.card-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Dashboard de Administración</h1>
                    <p>Bienvenido al panel de control de k8servers.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php
                    if (isset($_GET['error']) && $_GET['error'] === 'admin_auth_failed_redirect') {
                        echo '<div class="alert alert-danger">Error de autenticación de administrador.</div>';
                    }
                    ?>
                    <section class="content-section" style="background-color: transparent; box-shadow:none; padding:0;">
                        <div class="dashboard-grid">
                            <div class="dashboard-card">
                                <span class="card-icon">&#128101;</span>
                                <h3>Total de Usuarios</h3>
                                <p class="card-value"><?php echo $total_usuarios; ?></p>
                                <a href="gestionar_usuarios.php" class="card-link">Gestionar Usuarios &rarr;</a>
                            </div>
                            <div class="dashboard-card">
                                <span class="card-icon">&#128187;</span>
                                <h3>Sitios Web Activos</h3>
                                <p class="card-value"><?php echo $total_sitios_activos; ?></p>
                                <a href="gestionar_sitios_admin.php" class="card-link">Gestionar Sitios &rarr;</a>
                            </div>
                            <div class="dashboard-card">
                                <span class="card-icon">&#9993;</span>
                                <h3>Tickets Abiertos</h3>
                                <p class="card-value"><?php echo $total_tickets_abiertos; ?></p>
                                <a href="gestionar_tickets_admin.php" class="card-link">Gestionar Tickets &rarr;</a>
                            </div>
                            
                        </div>
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
