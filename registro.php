<?php
include 'menu.html';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="css/styles.css">
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
            max-width: 500px;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-group textarea {
            resize: none;
            height: 80px;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #007bff;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Registro de Usuario</h2>
        <form action="creacion.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="Nombre">Nombre:</label>
                <input type="text" id="Nombre" name="Nombre" maxlength="30" required>
            </div>

            <div class="form-group">
                <label for="Apellidos">Apellidos:</label>
                <input type="text" id="Apellidos" name="Apellidos" maxlength="40" required>
            </div>

            <div class="form-group">
                <label for="Email">Email:</label>
                <input type="email" id="Email" name="Email" maxlength="35" required>
            </div>

            <div class="form-group">
                <label for="Passwd">Contraseña:</label>
                <input type="password" id="Passwd" name="Passwd" maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="Telefono">Teléfono:</label>
                <input type="tel" id="Telefono" name="Telefono" maxlength="35" required>
            </div>

            <div class="form-group">
                <label for="Pais">País:</label>
                <select name="Paises" id="paises">
                    <option value="ES">España</option>
                    <option value="PT">Portugal</option>
                    <option value="FR">Francia</option>
                    <option value="AND">Andorra</option>
                </select>
            </div>

            <div class="form-group">
                <label for="Direccion">Dirección:</label>
                <textarea id="Direccion" name="Direccion"></textarea>
            </div>

            <div class="form-group">
                <label for="imagen">Imagen:</label>
                <input type="file" name="uploads">
                <button type="submit" name="subir">Subir archivo</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Registrar</button>
                <button type="reset" class="btn btn-secondary">Restablecer</button>
            </div>
        </form>
    </div>
</body>
</html>
