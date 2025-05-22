<?php
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

$usuarios = [];
$sql_usuarios = "SELECT ClienteID, Nombre, Apellidos, Email, Fecha_Registro FROM Cliente ORDER BY Fecha_Registro DESC";
$result_usuarios = $conn->query($sql_usuarios);
if ($result_usuarios && $result_usuarios->num_rows > 0) {
    while($row = $result_usuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Admin k8servers</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Gestionar Usuarios</h1>
                    <p>Administra las cuentas de los clientes registrados.</p>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section">
                        <h2 class="section-subtitle">Listado de Usuarios</h2>
                        <?php if (empty($usuarios)): ?>
                            <div class="empty-state"><p>No hay usuarios registrados.</p></div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre Completo</th>
                                            <th>Email</th>
                                            <th>Fecha Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($usuario['ClienteID']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellidos']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['Email']); ?></td>
                                            <td><?php echo date("d/m/Y H:i", strtotime($usuario['Fecha_Registro'])); ?></td>
                                            <td>
                                                <a href="ver_usuario_admin.php?id=<?php echo $usuario['ClienteID']; ?>" class="btn btn-xs btn-outline">Ver</a>
                                                <a href="editar_usuario_admin.php?id=<?php echo $usuario['ClienteID']; ?>" class="btn btn-xs btn-primary">Editar</a>
                                            </td>
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
