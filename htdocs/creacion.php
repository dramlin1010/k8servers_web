<?php
session_start();

$servidor = "mariadb-host-svc";
$usuario_db = "daniel";
$password_db = "Kt3xa6RqSAgdpskCZyuWfX";
$nombre_db = "k8servers";
$charset = 'utf8mb4';

$Nombre = $_POST["Nombre"] ?? '';
$Apellidos = $_POST["Apellidos"] ?? '';
$Email = $_POST["Email"] ?? '';
$Passwd = $_POST["Passwd"] ?? '';
$Telefono = $_POST["Telefono"] ?? '';  
$Pais = $_POST["Paises"] ?? '';
$Direccion = $_POST["Direccion"] ?? '';

if (empty($Nombre) || empty($Apellidos) || empty($Email) || empty($Passwd) || empty($Pais)) {
    $_SESSION['error_message'] = "Error: Todos los campos obligatorios (Nombre, Apellidos, Email, Contraseña, País) deben ser completados.";
    header("Location: registro.php");
    exit();
}

$PasswdHashed = password_hash($Passwd, PASSWORD_DEFAULT);

$conexion_servidor = new mysqli($servidor, $usuario_db, $password_db);

if ($conexion_servidor->connect_error) {
    $_SESSION['error_message'] = "Error crítico de conexión al servidor MySQL: " . $conexion_servidor->connect_error;
    header("Location: registro.php");
    exit();
}

$sql_crear_db = "CREATE DATABASE IF NOT EXISTS `$nombre_db` CHARACTER SET $charset COLLATE {$charset}_unicode_ci";
if (!$conexion_servidor->query($sql_crear_db)) {
    $_SESSION['error_message'] = "Error al crear la base de datos '$nombre_db': " . $conexion_servidor->error;
    $conexion_servidor->close();
    header("Location: registro.php");
    exit();
}
$conexion_servidor->close();

$conexion_db_especifica = new mysqli($servidor, $usuario_db, $password_db, $nombre_db);

if ($conexion_db_especifica->connect_error) {
    $_SESSION['error_message'] = "Error crítico de conexión a la base de datos '$nombre_db': " . $conexion_db_especifica->connect_error;
    header("Location: registro.php");
    exit();
}

$tablas_sql = [
    "CREATE TABLE IF NOT EXISTS Cliente (
        ClienteID INT AUTO_INCREMENT PRIMARY KEY,
        Nombre VARCHAR(50) NOT NULL,
        Apellidos VARCHAR(70) NOT NULL,
        Email VARCHAR(100) NOT NULL UNIQUE,
        Passwd VARCHAR(255) NOT NULL,
        Telefono VARCHAR(20),
        Pais VARCHAR(50),
        Direccion VARCHAR(150),
        Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        Token VARCHAR(255) NULL,
        TokenExpira DATETIME NULL
    )",
    "CREATE TABLE IF NOT EXISTS Plan_Hosting (
        PlanHostingID VARCHAR(50) PRIMARY KEY,
        NombrePlan VARCHAR(100) NOT NULL,
        Descripcion TEXT,
        Precio DECIMAL(10, 2) NOT NULL,
        Activo BOOLEAN NOT NULL DEFAULT TRUE
    )",
    "CREATE TABLE IF NOT EXISTS SitioWeb (
        SitioID INT AUTO_INCREMENT PRIMARY KEY,
        ClienteID INT NOT NULL,
        PlanHostingID VARCHAR(50) NOT NULL,
        SubdominioElegido VARCHAR(63) NOT NULL,
        DominioCompleto VARCHAR(255) NOT NULL UNIQUE,
        EstadoServicio VARCHAR(30) NOT NULL DEFAULT 'pendiente_pago',
        FechaContratacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FechaProximaRenovacion DATE,
        FOREIGN KEY (ClienteID) REFERENCES Cliente(ClienteID) ON DELETE CASCADE,
        FOREIGN KEY (PlanHostingID) REFERENCES Plan_Hosting(PlanHostingID)
    )",
    "CREATE TABLE IF NOT EXISTS Factura (
        FacturaID INT AUTO_INCREMENT PRIMARY KEY,
        ClienteID INT NOT NULL,
        SitioID INT NULL,
        Descripcion VARCHAR(255) NOT NULL,
        FechaEmision DATE NOT NULL,
        FechaVencimiento DATE,
        Monto DECIMAL(10, 2) NOT NULL,
        Estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
        MetodoPago VARCHAR(50) NULL,
        TransaccionID VARCHAR(100) NULL,
        FechaPago DATETIME NULL,
        FOREIGN KEY (ClienteID) REFERENCES Cliente(ClienteID) ON DELETE RESTRICT,
        FOREIGN KEY (SitioID) REFERENCES SitioWeb(SitioID) ON DELETE SET NULL
    )",
    "CREATE TABLE IF NOT EXISTS Ticket_Soporte (
        TicketID INT AUTO_INCREMENT PRIMARY KEY,
        ClienteID INT NOT NULL,
        SitioID INT NULL,
        FechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UltimaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        Asunto VARCHAR(150) NOT NULL,
        Estado VARCHAR(20) NOT NULL DEFAULT 'abierto',
        Prioridad VARCHAR(10) NOT NULL DEFAULT 'media',
        FOREIGN KEY (ClienteID) REFERENCES Cliente(ClienteID) ON DELETE CASCADE,
        FOREIGN KEY (SitioID) REFERENCES SitioWeb(SitioID) ON DELETE SET NULL
    )",
    "CREATE TABLE IF NOT EXISTS Mensaje_Ticket (
        MensajeID INT AUTO_INCREMENT PRIMARY KEY,
        TicketID INT NOT NULL,
        UsuarioID INT NULL,
        EsAdmin BOOLEAN NOT NULL DEFAULT FALSE,
        Contenido TEXT NOT NULL,
        FechaEnvio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (TicketID) REFERENCES Ticket_Soporte(TicketID) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS Log_Actividad (
        LogID INT AUTO_INCREMENT PRIMARY KEY,
        ClienteID INT NULL,
        TipoActividad VARCHAR(50) NOT NULL,
        Descripcion TEXT,
        DireccionIP VARCHAR(45),
        UserAgent VARCHAR(255),
        FechaLog DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tablas_sql as $sql) {
    if (!$conexion_db_especifica->query($sql)) {
        $_SESSION['error_message'] = "Error al crear/verificar una tabla: " . $conexion_db_especifica->error;
        $conexion_db_especifica->close();
        header("Location: registro.php");
        exit();
    }
}

$check_plan_sql = "SELECT PlanHostingID FROM Plan_Hosting WHERE PlanHostingID = 'developer_pro'";
$result_plan = $conexion_db_especifica->query($check_plan_sql);
if ($result_plan && $result_plan->num_rows == 0) {
    $sql_insert_plan = "INSERT INTO Plan_Hosting (PlanHostingID, NombrePlan, Descripcion, Precio, Activo)
    VALUES ('developer_pro', 'Developer Pro Hosting', 'Nuestro plan todo incluido para desarrolladores y creativos.', 25.00, TRUE)";
    if (!$conexion_db_especifica->query($sql_insert_plan)) {
        $_SESSION['error_message'] = "Error al insertar plan Developer Pro: " . $conexion_db_especifica->error;
        $conexion_db_especifica->close();
        header("Location: registro.php");
        exit();
    }
}


$emailEscapado = $conexion_db_especifica->real_escape_string($Email);
$sql_check_email = "SELECT ClienteID FROM Cliente WHERE Email = '$emailEscapado'";
$resultado_check = $conexion_db_especifica->query($sql_check_email);

if ($resultado_check === false) {
    $_SESSION['error_message'] = "Error al verificar el email: " . $conexion_db_especifica->error;
    $conexion_db_especifica->close();
    header("Location: registro.php");
    exit();
} elseif ($resultado_check->num_rows > 0) {
    $_SESSION['error_message'] = "Error: Este email ('" . htmlspecialchars($Email) . "') ya está registrado.";
    $conexion_db_especifica->close();
    header("Location: registro.php");
    exit();
} else {
    $nombreEscapado = $conexion_db_especifica->real_escape_string($Nombre);
    $apellidosEscapados = $conexion_db_especifica->real_escape_string($Apellidos);
    $telefonoEscapado = $conexion_db_especifica->real_escape_string($Telefono);
    $paisEscapado = $conexion_db_especifica->real_escape_string($Pais);
    $direccionEscapada = $conexion_db_especifica->real_escape_string($Direccion);

    $insertar_datos = "INSERT INTO Cliente (Nombre, Apellidos, Email, Passwd, Telefono, Pais, Direccion) 
                       VALUES ('$nombreEscapado', '$apellidosEscapados', '$emailEscapado', '$PasswdHashed', '$telefonoEscapado', '$paisEscapado', '$direccionEscapada')";

    if ($conexion_db_especifica->query($insertar_datos) === TRUE) {
        $_SESSION['success_message'] = "¡Registro completado exitosamente! Ahora puedes iniciar sesión.";
        $conexion_db_especifica->close();
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error al insertar los datos del cliente: " . $conexion_db_especifica->error;
        $conexion_db_especifica->close();
        header("Location: registro.php");
        exit();
    }
}
?>
