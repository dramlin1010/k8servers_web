<?php
session_start();

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy();
    setcookie("session_token", "", time() - 3600, "/");
    header("Location: login.php");
    exit();
}

$tiene_servicio_activo = false;

if ($tiene_servicio_activo) {

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratar Hosting - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Activar tu Servicio de Hosting</h1>
                    <p>Estás a un paso de lanzar tu presencia online con k8servers.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php if ($tiene_servicio_activo): ?>
                        <section class="content-section">
                            <h2 class="section-subtitle">Servicio ya Activo</h2>
                            <div class="empty-state">
                                <i class="icon-big">&#128736;</i>
                                <p>¡Buenas noticias! Ya tienes nuestro plan de hosting activo.</p>
                                <p>Puedes gestionar tu sitio desde la sección "Mis Sitios".</p>
                                <a href="mis_sitios.php" class="btn btn-primary">Ir a Mis Sitios</a>
                            </div>
                        </section>
                    <?php else: ?>
                        <section class="content-section">
                            <h2 class="section-subtitle">Plan Developer Pro - 25€/mes</h2>
                            <div class="pricing-details-panel">
                                <p>Obtén todas las características de nuestro potente plan de hosting:</p>
                                <ul>
                                    <li>Almacenamiento SSD NVMe Ilimitado*</li>
                                    <li>Transferencia Mensual Ilimitada*</li>
                                    <li>Cuentas FTP/SFTP Ilimitadas</li>
                                    <li>Bases de Datos Ilimitadas</li>
                                    <li>Certificado SSL Gratis (Let's Encrypt)</li>
                                    <li>Soporte Multi-PHP, Node.js, Python</li>
                                    <li>Copias de Seguridad Diarias</li>
                                    <li>Soporte Técnico Prioritario 24/7</li>
                                </ul>
                                <small style="display:block; margin-top:10px; margin-bottom:20px; color: var(--text-color-muted);">*Sujeto a política de uso razonable.</small>
                                
                                <form action="procesar_contratacion.php" method="POST">
                                    <input type="hidden" name="plan_id" value="developer_pro">
                                    <input type="hidden" name="cliente_id" value="<?php echo $_SESSION['ClienteID']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="subdominio_elegido">Elige el nombre para tu sitio (subdominio):</label>
                                        <div class="input-group-subdomain">
                                            <input type="text" id="subdominio_elegido" name="subdominio_elegido" placeholder="ej: subdominio1" required pattern="[a-zA-Z0-9-]{3,30}" title="De 3 a 30 caracteres. Solo letras, números y guiones. No puede empezar ni terminar con guion.">
                                            <span class="subdomain-suffix">.k8servers.es</span>
                                        </div>
                                        <small>Esta será la dirección de tu sitio: <strong>nombre-elegido</strong>.k8servers.es. <br>Asegúrate de que sea único y fácil de recordar. Podrás apuntar un dominio propio más tarde si lo deseas.</small>
                                    </div>

                                    <div class="form-group-checkbox" style="margin-bottom: 20px;">
                                        <input type="checkbox" id="terminos_servicio" name="terminos_servicio" required>
                                        <label for="terminos_servicio">Acepto los <a href="terminos.php" target="_blank">Términos y Condiciones del Servicio</a>.</label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-lg">Confirmar y Activar Hosting</button>
                                </form>
                            </div>
                        </section>
                    <?php endif; ?>
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
