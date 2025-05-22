<?php
session_start();
include 'menu.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <main class="page-content">
        <section class="info-section animated-section">
            <div class="container">
                <div class="form-wrapper" style="max-width: 600px; margin: 40px auto; text-align: center;">
                    <h2 class="section-title form-title">Recuperar Contraseña</h2>
                    
                    <div class="alert alert-info" style="margin-top: 20px; margin-bottom: 30px; text-align: left;">
                        <p style="margin-bottom: 10px; font-size: 1.1rem;"><strong>Característica en desarrollo.</strong></p>
                        <p style="color: var(--text-color-muted);">Actualmente, la función de recuperación automática de contraseña está en proceso de implementación.</p>
                        <p style="color: var(--text-color-muted);">Si has olvidado tu contraseña y necesitas recuperarla, por favor, ponte en contacto con nuestro equipo de soporte técnico.</p>
                        <p style="margin-top: 15px; color: var(--text-color-muted);">Puedes hacerlo a través de nuestro correo electrónico: <a href="mailto:soporte@k8servers.es">soporte@k8servers.es</a> o llamando al +34 123 456 789.</p>
                        <p style="margin-top: 15px; color: var(--text-color-muted);">Lamentamos las molestias.</p>
                    </div>

                    <div class="form-actions" style="justify-content: center;">
                        <a href="login.php" class="btn btn-primary">Volver a Iniciar Sesión</a>
                        <a href="index.php" class="btn btn-outline" style="margin-left: 15px;">Ir a Inicio</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const animatedSections = document.querySelectorAll('.animated-section');
        if (animatedSections.length > 0) {
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
        }
    });
</script>
</body>
</html>
