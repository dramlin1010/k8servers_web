<?php
require_once 'config.php';
include 'menu.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <main class="page-content">
        <section class="registration-section animated-section">
            <div class="container">
                <div class="form-wrapper">
                    <h2 class="section-title form-title">Crea tu Cuenta en k8servers</h2>
                    <p class="form-subtitle">Únete a nuestra plataforma y toma el control de tu hosting.</p>
                    <form action="creacion.php" method="post" class="registration-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="Nombre">Nombre</label>
                                <input type="text" id="Nombre" name="Nombre" maxlength="30" required placeholder="Tu nombre">
                            </div>
                            <div class="form-group">
                                <label for="Apellidos">Apellidos</label>
                                <input type="text" id="Apellidos" name="Apellidos" maxlength="40" required placeholder="Tus apellidos">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="Email">Email</label>
                            <input type="email" id="Email" name="Email" maxlength="35" required placeholder="tu.correo@ejemplo.com">
                        </div>

                        <div class="form-group">
                            <label for="Passwd">Contraseña</label>
                            <input type="password" id="Passwd" name="Passwd" maxlength="100" required placeholder="Crea una contraseña segura">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="Telefono">Teléfono</label>
                                <input type="tel" id="Telefono" name="Telefono" maxlength="35" required placeholder="+34 123 456 789">
                            </div>
                            <div class="form-group">
                                <label for="Paises">País</label>
                                <select name="Paises" id="Paises">
                                    <option value="ES">España</option>
                                    <option value="PT">Portugal</option>
                                    <option value="FR">Francia</option>
                                    <option value="AND">Andorra</option>
                                    <option value="OTRO">Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="Direccion">Dirección (Opcional)</label>
                            <textarea id="Direccion" name="Direccion" placeholder="Tu dirección completa"></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Crear Cuenta</button>
                            <button type="reset" class="btn btn-outline">Restablecer</button>
                        </div>
                         <p class="login-link">¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión</a></p>
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
