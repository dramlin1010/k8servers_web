<?php
require_once 'config.php';
include 'menu.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <main class="page-content">
        <section class="login-section animated-section">
            <div class="container">
                <div class="form-wrapper">
                    <h2 class="section-title form-title">Accede a tu Cuenta</h2>
                    <p class="form-subtitle">Bienvenido de nuevo a k8servers. Ingresa tus credenciales.</p>
                    <form action="acceso_usuario.php" method="post" class="login-form">
                        <div class="form-group">
                            <label for="Email">Email</label>
                            <input type="email" id="Email" name="Email" maxlength="35" required placeholder="tu.correo@ejemplo.com">
                        </div>

                        <div class="form-group">
                            <label for="Passwd">Contraseña</label>
                            <input type="password" id="Passwd" name="Passwd" maxlength="100" required placeholder="Tu contraseña">
                        </div>

                        <div class="form-options">
                            <div class="form-group-checkbox">
                                <input type="checkbox" id="rememberMe" name="rememberMe">
                                <label for="rememberMe">Recordarme</label>
                            </div>
                            <a href="recuperar_contrasena.php" class="forgot-password-link">¿Olvidaste tu contraseña?</a>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Acceder</button>
                        </div>
                         <p class="register-link">¿Aún no tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>
                    </form>
                </div>
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
