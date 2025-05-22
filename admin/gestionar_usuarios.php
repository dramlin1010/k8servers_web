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
$sql_usuarios = "SELECT ClienteID, Nombre, Apellidos, Email, Fecha_Registro, Telefono, Pais FROM Cliente ORDER BY Fecha_Registro DESC";
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
    <style>
        .kebab-menu-container {
            position: relative;
            display: inline-block;
        }
        .kebab-toggle {
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            font-size: 1.4rem;
            line-height: 1;
            color: var(--text-color-muted);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kebab-toggle:hover, .kebab-toggle:focus {
            background-color: var(--bg-color);
            color: var(--primary-color);
        }
        .kebab-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: var(--card-bg);
            box-shadow: var(--box-shadow-current);
            border-radius: var(--border-radius);
            z-index: 100;
            min-width: 160px;
            border: 1px solid var(--border-color);
            padding: 5px 0;
        }
        .kebab-dropdown.show {
            display: block;
        }
        .kebab-dropdown a,
        .kebab-dropdown button {
            display: block;
            width: 100%;
            padding: 10px 15px;
            text-align: left;
            font-size: 0.9rem;
            color: var(--text-color);
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .kebab-dropdown a:hover,
        .kebab-dropdown button:hover {
            background-color: var(--primary-color);
            color: var(--bg-color);
        }
        .dark-mode .kebab-dropdown a:hover,
        .dark-mode .kebab-dropdown button:hover {
            color: #0f172a;
        }
        .light-mode .kebab-dropdown a:hover,
        .light-mode .kebab-dropdown button:hover {
            color: #ffffff;
        }
        .kebab-dropdown button.delete-action:hover {
            background-color: var(--accent-color);
        }
        .dark-mode .kebab-dropdown button.delete-action:hover {
            color: #0f172a;
        }
        .light-mode .kebab-dropdown button.delete-action:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include("menu_admin.php"); ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Gestionar Usuarios</h1>
                    <p>Administra las cuentas de los clientes registrados en k8servers.</p>
                </div>
            </header>
            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php
                    if (isset($_SESSION['success_message'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                        unset($_SESSION['success_message']);
                    }
                    if (isset($_SESSION['error_message'])) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                        unset($_SESSION['error_message']);
                    }
                    ?>
                    <section class="content-section">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 class="section-subtitle" style="margin-bottom: 0; border-bottom: none;">Listado de Usuarios</h2>
                            <a href="crear_usuario_admin.php" class="btn btn-primary"><i class="icon" style="margin-right: 5px;">&#43;</i> Crear Nuevo Usuario</a>
                        </div>
                        <?php if (empty($usuarios)): ?>
                            <div class="empty-state">
                                <i class="icon-big">&#128101;</i>
                                <p>No hay usuarios registrados en el sistema.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre Completo</th>
                                            <th>Email</th>
                                            <th>Teléfono</th>
                                            <th>País</th>
                                            <th>Fecha Registro</th>
                                            <th style="text-align:center;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($usuario['ClienteID']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellidos']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['Email']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['Telefono'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['Pais'] ?? 'N/A'); ?></td>
                                            <td><?php echo $usuario['Fecha_Registro'] ? date("d/m/Y H:i", strtotime($usuario['Fecha_Registro'])) : 'N/A'; ?></td>
                                            <td style="text-align:center;">
                                                <div class="kebab-menu-container">
                                                    <button class="kebab-toggle" aria-haspopup="true" aria-expanded="false" title="Acciones para usuario <?php echo htmlspecialchars($usuario['ClienteID']); ?>">
                                                        &#8942; 
                                                    </button>
                                                    <div class="kebab-dropdown">
                                                        <a href="ver_usuario_admin.php?id=<?php echo $usuario['ClienteID']; ?>">Ver Detalles</a>
                                                        <a href="editar_usuario_admin.php?id=<?php echo $usuario['ClienteID']; ?>">Editar Usuario</a>
                                                        <form action="eliminar_usuario_admin.php" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este usuario y todos sus datos asociados? Esta acción no se puede deshacer.');" style="margin:0;">
                                                            <input type="hidden" name="cliente_id" value="<?php echo $usuario['ClienteID']; ?>">
                                                            <button type="submit" class="delete-action">Eliminar Usuario</button>
                                                        </form>
                                                    </div>
                                                </div>
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

        const kebabToggles = document.querySelectorAll('.kebab-toggle');
        kebabToggles.forEach(toggle => {
            toggle.addEventListener('click', function(event) {
                event.stopPropagation();
                let dropdown = this.nextElementSibling;
                
                document.querySelectorAll('.kebab-dropdown.show').forEach(openDropdown => {
                    if (openDropdown !== dropdown) {
                        openDropdown.classList.remove('show');
                        openDropdown.previousElementSibling.setAttribute('aria-expanded', 'false');
                    }
                });

                dropdown.classList.toggle('show');
                this.setAttribute('aria-expanded', dropdown.classList.contains('show').toString());
            });
        });

        document.addEventListener('click', function(event) {
            document.querySelectorAll('.kebab-dropdown.show').forEach(openDropdown => {
                const toggle = openDropdown.previousElementSibling;
                if (!toggle.contains(event.target) && !openDropdown.contains(event.target)) {
                    openDropdown.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        });
    });
</script>
</body>
</html>
