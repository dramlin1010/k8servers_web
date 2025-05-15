<?php
include("menu_admin.html");
session_start();
require("../conexion.php");

if (!isset($_COOKIE['admin_session']) || $_COOKIE['admin_session'] !== session_id()) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
    $cliente_id = intval($_POST['eliminar_id']);
    $stmt = $conn->prepare("DELETE FROM Cliente WHERE ClienteID = ?");
    $stmt->bind_param("i", $cliente_id);
    if ($stmt->execute()) {
        echo "<p>Usuario eliminado correctamente.</p>";
    } else {
        echo "<p>Error al eliminar el usuario.</p>";
    }
    $stmt->close();
}

$sql = "SELECT ClienteID, Nombre, Apellidos, Email, Telefono, Pais, Fecha_Registro FROM Cliente";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 20px;
            margin: auto;
            max-width: 1200px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f4f4f4;
        }
        .actions button {
            margin-right: 5px;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }
        .btn-details {
            background-color: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestión de Usuarios</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellidos</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>País</th>
                    <th>Fecha de Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['ClienteID']); ?></td>
                            <td><?php echo htmlspecialchars($row['Nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['Apellidos']); ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td><?php echo htmlspecialchars($row['Telefono']); ?></td>
                            <td><?php echo htmlspecialchars($row['Pais']); ?></td>
                            <td><?php echo htmlspecialchars($row['Fecha_Registro']); ?></td>
                            <td class="actions">
                                <form action="usuarios.php" method="post" style="display:inline-block;">
                                    <input type="hidden" name="eliminar_id" value="<?php echo $row['ClienteID']; ?>">
                                    <button type="submit" class="btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No hay usuarios registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php $conn->close(); ?>
