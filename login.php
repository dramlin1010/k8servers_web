<?php
include 'menu.html';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Usuario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

    </style>
</head>
<body>
    <div class="form-container">
        <form action="acceso_usuario.php" method="post" enctype="multipart/form-data" class="login-form">
            <h2>Iniciar Sesión</h2>
            <div class="form-group">
                <label for="Email">Email:</label>
                <input type="text" id="Email" name="Email" maxlength="35" placeholder="Tu correo electrónico" required>
            </div>
            <div class="form-group">
                <label for="Passwd">Contraseña:</label>
                <input type="password" id="Passwd" name="Passwd" maxlength="100" placeholder="Tu contraseña" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Acceder</button>
                <button type="reset" class="btn btn-secondary">Limpiar</button>
            </div>
        </form>
    </div>
</body>
</html>
