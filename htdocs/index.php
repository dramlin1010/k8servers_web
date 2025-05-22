<?php
include 'menu.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>k8servers - Tu Hosting, Tus Reglas</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <header class="hero">
        <div class="container hero-content">
            <h1>Tu Espacio Web, Sin Límites. Potencia Pura para Tus Proyectos.</h1>
            <p>En k8servers, te damos el control total. Sube tus archivos, configura tu entorno y lanza webs imparables. Rendimiento y libertad para desarrolladores y creativos.</p>
            <a href="registro.php" class="btn btn-primary btn-lg">Empieza Ahora</a>
        </div>
    </header>

    <main>
        <section id="features" class="features animated-section">
            <div class="container">
                <h2 class="section-title">Libertad y Rendimiento Sin Igual</h2>
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="icon-placeholder">FTP</div>
                        <h3>Acceso Total y Directo</h3>
                        <p>Sube y gestiona tus archivos vía FTP/SFTP. Controla cada aspecto de tu proyecto web sin restricciones.</p>
                    </div>
                    <div class="feature-item">
                        <div class="icon-placeholder">SSD</div>
                        <h3>Rendimiento Superior</h3>
                        <p>Servidores optimizados con discos SSD NVMe y la última tecnología para una velocidad de carga insuperable.</p>
                    </div>
                    <div class="feature-item">
                        <div class="icon-placeholder">SEC</div>
                        <h3>Seguridad Robusta</h3>
                        <p>Protección DDoS, firewalls avanzados y copias de seguridad regulares para tu total tranquilidad.</p>
                    </div>
                    <div class="feature-item">
                        <div class="icon-placeholder">PHP</div>
                        <h3>Entorno Flexible</h3>
                        <p>Soporte para múltiples versiones de PHP, Node.js, Python y más. Configura el entorno a tu medida.</p>
                    </div>
                    <div class="feature-item">
                        <div class="icon-placeholder">DB</div>
                        <h3>Bases de Datos Potentes</h3>
                        <p>Crea y gestiona bases de datos MySQL o PostgreSQL con herramientas como phpMyAdmin.</p>
                    </div>
                    <div class="feature-item">
                        <div class="icon-placeholder">24/7</div>
                        <h3>Soporte Experto</h3>
                        <p>Nuestro equipo técnico está disponible 24/7 para ayudarte con cualquier consulta o incidencia.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="how-it-works animated-section">
            <div class="container">
                <h2 class="section-title">Despliega tu Web en Minutos</h2>
                <div class="how-it-works-grid">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <h3>Regístrate</h3>
                        <p>Crea tu cuenta en k8servers de forma rápida y sencilla. Elige el plan que se adapte a ti.</p>
                    </div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <h3>Sube Tus Archivos</h3>
                        <p>Conéctate vía FTP/SFTP o usa nuestro gestor de archivos para subir el contenido de tu web.</p>
                    </div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <h3>Configura y Lanza</h3>
                        <p>Apunta tu dominio, configura tus bases de datos si es necesario, ¡y tu web estará online!</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" class="pricing animated-section">
            <div class="container">
                <h2 class="section-title">Un Plan Único, Potencia Ilimitada</h2>
                <div class="pricing-plan">
                    <h3>Plan Developer Pro</h3>
                    <p class="price">25€<span>/mes</span></p>
                    <p>Todo el poder y la flexibilidad que necesitas para tus proyectos web más ambiciosos.</p>
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
                    <small style="display:block; margin-top:-20px; margin-bottom:20px; color: var(--text-color-muted);">*Sujeto a política de uso razonable.</small>
                    <a href="registro.php?plan=developer" class="btn btn-primary btn-block">Contratar Developer Pro</a>
                </div>
            </div>
        </section>

        <section class="cta-final animated-section">
            <div class="container">
                <h2>¿Listo para Tomar el Control Total de tu Hosting?</h2>
                <p>Únete a la comunidad de desarrolladores y creativos que eligen k8servers por su rendimiento, flexibilidad y soporte experto. ¡Tu proyecto merece lo mejor!</p>
                <a href="registro.php" class="btn btn-secondary btn-lg">Crea tu Cuenta Ahora</a>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const animatedSections = document.querySelectorAll('.animated-section');
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        animatedSections.forEach(section => {
            observer.observe(section);
        });
    });
</script>
</body>
</html>
