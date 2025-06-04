<?php
require_once '../config.php';

include("menu_admin.html");
session_start();

if (!isset($_COOKIE['admin_session']) || $_COOKIE['admin_session'] !== session_id()) {
    header("Location: ../login.php");
    exit();
}

require_once("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estado'], $_POST['ticket_id'])) {
    $nuevo_estado = $_POST['estado'];
    $ticket_id = intval($_POST['ticket_id']);
    $stmt = $conn->prepare("UPDATE Ticket_Soporte SET Estado = ? WHERE TicketID = ?");
    $stmt->bind_param("si", $nuevo_estado, $ticket_id);
    $stmt->execute();
    $stmt->close();
}

$sql = "SELECT * FROM Ticket_Soporte";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets de Soporte</title>
    <link rel="stylesheet" href="../css/panel_admin.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            display: flex;
        }

        .main-content {
            margin-left: 240px; /* Ancho del menú vertical */
            padding: 20px;
            width: calc(100% - 240px);
            min-height: 100vh;
            background-color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        table th {
            background-color: #f4f4f4;
        }

        .form-inline {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-inline select, .form-inline button {
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h1>Tickets de Soporte</h1>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Asunto</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Cliente ID</th>
                    <th>Cuenta Hosting ID</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['TicketID']; ?></td>
                            <td><?php echo $row['FechaCreacion']; ?></td>
                            <td><?php echo $row['Asunto']; ?></td>
                            <td><?php echo $row['Descripcion']; ?></td>
                            <td><?php echo ucfirst($row['Estado']); ?></td>
                            <td><?php echo ucfirst($row['Prioridad']); ?></td>
                            <td><?php echo $row['ClienteID']; ?></td>
                            <td><?php echo $row['CuentaHostingID']; ?></td>
                            <td>
                                <form method="post" class="form-inline">
                                    <input type="hidden" name="ticket_id" value="<?php echo $row['TicketID']; ?>">
                                    <select name="estado">
                                        <option value="abierto" <?php echo $row['Estado'] === 'abierto' ? 'selected' : ''; ?>>Abierto</option>
                                        <option value="pendiente" <?php echo $row['Estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="cerrado" <?php echo $row['Estado'] === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                                    </select>
                                    <button type="submit">Actualizar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">No hay tickets registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
