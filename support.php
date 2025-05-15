<?php
session_start();

if (!isset($_SESSION['ClienteID'])) {
    header("Location: login.php");
    exit();
}

require 'conexion.php';

$ClienteID = $_SESSION['ClienteID'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $asunto = mysqli_real_escape_string($conn, $_POST['asunto']);
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
    $prioridad = mysqli_real_escape_string($conn, $_POST['prioridad']);
    $estado = 'abierto';
    $cuentaHostingID = intval($_POST['cuentaHostingID']);
    $fechaCreacion = date('Y-m-d H:i:s');

    $sql = "INSERT INTO Ticket_Soporte (FechaCreacion, Asunto, Descripcion, Estado, Prioridad, ClienteID, CuentaHostingID) 
            VALUES ('$fechaCreacion', '$asunto', '$descripcion', '$estado', '$prioridad', $ClienteID, $cuentaHostingID)";

    if (mysqli_query($conn, $sql)) {
        $successMessage = "Ticket enviado con éxito. Su ticket ha sido recibido por el Administrador.";
    } else {
        $errorMessage = "Error al enviar el ticket: " . mysqli_error($conn);
    }
}

$sql = "SELECT CuentaHostingID, Dominio FROM Cuenta_Hosting WHERE ClienteID = $ClienteID";
$result = mysqli_query($conn, $sql);
$cuentasHosting = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cuentasHosting[] = $row;
    }
} else {
    $errorMessage = "Error al obtener las cuentas de hosting: " . mysqli_error($conn);
}

mysqli_close($conn);
include("menu_vertical_usuario.html");

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - Enviar Ticket</title>
    <link rel="stylesheet" href="menu_vertical.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
        }

        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-container h1 {
            text-align: center;
        }

        label {
            display: block;
            margin: 10px 0 5px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            padding: 10px 15px;
            background-color: #9c2531;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
        }

        button:hover {
            background-color: #b33644;
        }

        .message {
            text-align: center;
            font-size: 1rem;
            margin: 20px 0;
        }

        .message.success {
            color: green;
        }

        .message.error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="form-container">
            <h1>Enviar Ticket de Soporte</h1>
            
            <?php if (isset($successMessage)): ?>
                <div class="message success"><?php echo $successMessage; ?></div>
            <?php endif; ?>

            <?php if (isset($errorMessage)): ?>
                <div class="message error"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <form action="support.php" method="POST">
                <label for="asunto">Asunto</label>
                <input type="text" id="asunto" name="asunto" required>

                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="5" required></textarea>

                <label for="prioridad">Prioridad</label>
                <select id="prioridad" name="prioridad" required>
                    <option value="alta">Alta</option>
                    <option value="media">Media</option>
                    <option value="baja">Baja</option>
                </select>

                <label for="cuentaHostingID">Cuenta de Hosting</label>
                <select id="cuentaHostingID" name="cuentaHostingID" required>
                    <?php foreach ($cuentasHosting as $cuenta): ?>
                        <option value="<?php echo $cuenta['CuentaHostingID']; ?>"><?php echo htmlspecialchars($cuenta['Dominio']); ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Enviar Ticket</button>
            </form>
        </div>
    </div>
</body>
</html>
