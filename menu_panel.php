<?php
require_once 'config.php';
$nombreUsuario = $_SESSION['NombreCliente'] ?? 'Usuario';
?>
<aside class="panel-sidebar">
    <div class="sidebar-header">
        <a href="panel_usuario.php" class="panel-logo">k8servers</a>
        <button id="theme-toggle-panel" class="theme-toggle-btn" title="Cambiar tema">
            <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM12 5c.7 0 1.37.1 2 .29V3h-4v2.29c.63-.19 1.3-.29 2-.29zm0 14c-.7 0-1.37-.1-2-.29V21h4v-2.29c-.63.19-1.3.29-2 .29zM4.22 5.64l1.42-1.42L7.05 5.63 5.63 7.05 4.22 5.64zM16.95 18.37l1.42-1.42 1.41 1.41-1.42 1.42-1.41-1.41zM21 11h-2v2h2v-2zm-19 0H0v2h2v-2zM5.63 16.95l1.42 1.42L5.64 19.78 4.22 18.37l1.41-1.42zM18.37 4.22l1.42 1.42L19.78 7.05l-1.42-1.41-1.42-1.42z"/></svg>
            <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M10 2c-1.82 0-3.53.5-5 1.35C7.99 5.08 10 8.3 10 12s-2.01 6.92-5 8.65C6.47 21.5 8.18 22 10 22c5.52 0 10-4.48 10-10S15.52 2 10 2z"/></svg>
        </button>
    </div>
    <nav class="panel-nav">
        <ul>
            <li class="nav-section-title"><span>General</span></li>
            <li><a href="panel_usuario.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'panel_usuario.php' ? 'active' : ''; ?>"><i class="icon">&#8962;</i> Dashboard</a></li>
            <li><a href="mis_sitios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mis_sitios.php' ? 'active' : ''; ?>"><i class="icon">&#128187;</i> Mis Sitios</a></li>

            <li class="nav-section-title"><span>Facturación</span></li>
            <li><a href="facturas.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'facturas.php' ? 'active' : ''; ?>"><i class="icon">&#128179;</i> Facturas</a></li>

            <li class="nav-section-title"><span>Ayuda</span></li>
            <li><a href="support.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : ''; ?>"><i class="icon">&#9993;</i> Tickets de Soporte</a></li>
            <li><a href="faq.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'faq.php' ? 'active' : ''; ?>"><i class="icon">&#10067;</i> FAQ</a></li>

            <li class="nav-section-title"><span>Cuenta</span></li>
            <li><a href="perfil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>"><i class="icon">&#128100;</i> Mi Perfil</a></li>
            <li><a href="logout.php"><i class="icon">&#128682;</i> Cerrar Sesión</a></li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <p>&copy; <?php echo date('Y'); ?> k8servers</p>
    </div>
</aside>
