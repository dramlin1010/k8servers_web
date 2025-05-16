<?php
$servidor = "localhost";
$usuario = "daniel";
$password = "Kt3xa6RqSAgdpskCZyuWfX";
$DB = "k8servers";

$Nombre = $_POST["Nombre"];
$Apellidos = $_POST["Apellidos"];
$Email = $_POST["Email"];
$Passwd = $_POST["Passwd"];
$Telefono = $_POST["Telefono"];  
$Pais = $_POST["Paises"];
$Direccion = $_POST["Direccion"];

$PasswdHashed = password_hash($Passwd, PASSWORD_DEFAULT);

$directorio_subida = 'uploads/';

if (isset($_POST["subir"]) && isset($_FILES["uploads"])) {
    var_dump($_FILES);

    if ($_FILES['uploads']['error'] !== UPLOAD_ERR_OK) {
        echo "Error en la subida del archivo: " . $_FILES['uploads']['error'] . "<br>";
        exit();
    }

    $directorioTemp = $_FILES["uploads"]["tmp_name"];
    $nombrearchivo = $_FILES["uploads"]["name"];
    $tipoarchivo = $_FILES["uploads"]["type"];
    $tamanioarchivo = $_FILES["uploads"]["size"];

    $errores = 0;
    $respuesta = "+";

    if ($tamanioarchivo > 1048576) {
        echo "El archivo supera el tamaño permitido (1 MB).<br>";
        $errores++;
    }

    $tipos_permitidos = ["image/jpeg", "image/png", "image/gif"];
    if (!in_array($tipoarchivo, $tipos_permitidos)) {
        echo "El tipo de archivo no está permitido. Debe ser JPEG, PNG o GIF.<br>";
        $errores++;
    }

    if ($errores == 0) {
        if (!is_dir($directorio_subida)) {
            mkdir($directorio_subida, 0777, true);
            echo "Directorio 'uploads/' creado correctamente.<br>";
        }

        $nombreCompleto = $directorio_subida . $nombrearchivo;

        if (move_uploaded_file($directorioTemp, $nombreCompleto)) {
            echo "El archivo se ha subido correctamente.<br>";
            $respuesta = $nombreCompleto;
        } else {
            echo "Error al mover el archivo a la carpeta de destino.<br>";
            $respuesta = null;
        }
    } else {
        $respuesta = null;
    }
} else {
    echo "No se recibió ningún archivo.<br>";
    $respuesta = null;
}

$conexion = new mysqli($servidor, $usuario, $password);

if ($conexion -> connect_error) {
    die("". $conexion -> connect_error);
} else{
    print("La conexion ha sido exitosa.");
}

$conexion_DB = new mysqli($servidor, $usuario, $password, $DB);


$sql = "CREATE DATABASE $DB";

if ($conexion -> query($sql) == TRUE) {
    print("<br>La base de datos ha sido creada con exito<br>");
}
else{
    print("<br>La base de datos ya existe.<br>");
}

$sql_tabla = "CREATE TABLE Cliente (
    ClienteID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(30) NOT NULL,
    Apellidos VARCHAR(40) NOT NULL,
    Email VARCHAR(35) NOT NULL,
    Passwd varchar(100) NOT NULL,
    Telefono VARCHAR(11) NOT NULL,
    Pais VARCHAR(20) NOT NULL,
    Direccion VARCHAR(40) NOT NULL,
    Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Imagen blob
)";

$sql_tabla2 = "
CREATE TABLE Plan_Hosting (
    PlanHostingID INT AUTO_INCREMENT PRIMARY KEY,
    NombrePlan varchar(50) NOT NULL,
    Dominio VARCHAR(30) NOT NULL,
    SistemaOperativo VARCHAR(15) NOT NULL,
    Disco VARCHAR(5) NOT NULL,
    Precio INT NOT NULL
)";

$sql_tabla3 = "
CREATE TABLE Cuenta_Hosting (
    ClienteID INT NOT NULL,
    PlanHostingID INT NOT NULL,
    CuentaHostingID INT AUTO_INCREMENT PRIMARY KEY,
    Dominio varchar(30) NOT NULL,
    FechaInicio DATETIME NOT NULL,
    Estado VARCHAR(10) NOT NULL CHECK (Estado IN ('activo', 'inactivo', 'suspendido')),
    AnchoBandaUsado INT NOT NULL,
    FOREIGN KEY (ClienteID) REFERENCES Cliente(ClienteID),
    FOREIGN KEY (PlanHostingID) REFERENCES Plan_Hosting(PlanHostingID)
)";

$sql_tabla4 = "
CREATE TABLE Factura (
    ClienteID INT NOT NULL,
    CuentaHostingID INT NOT NULL,
    FacturaID INT AUTO_INCREMENT PRIMARY KEY,
    Descripcion VARCHAR(255) NOT NULL,
    FechaEmision DATETIME,
    FechaVencimiento DATETIME,
    SaldoTotal INT,
    Estado VARCHAR(10) NOT NULL CHECK (Estado IN ('pagado', 'pendiente')),
    FOREIGN KEY (ClienteID) REFERENCES Cliente(ClienteID),
    FOREIGN KEY (CuentaHostingID) REFERENCES Cuenta_Hosting(CuentaHostingID)
)";

$sql_tabla5 = "
CREATE TABLE Ticket_Soporte (
    TicketID INT AUTO_INCREMENT PRIMARY KEY,
    FechaCreacion DATETIME,
    Asunto VARCHAR(25) NOT NULL,
    Descripcion VARCHAR(100) NOT NULL,
    Estado VARCHAR(9) NOT NULL CHECK (Estado IN ('abierto', 'cerrado', 'pendiente')),
    Prioridad VARCHAR(5) NOT NULL,
    ClienteID INT NOT NULL,
    CuentaHostingID INT NOT NULL,
    FOREIGN KEY (ClienteID) REFERENCES Cliente(ClienteID),
    FOREIGN KEY (CuentaHostingID) REFERENCES Cuenta_Hosting(CuentaHostingID)
)";

if ($conexion_DB -> query($sql_tabla) == TRUE) {
    print("<br> La tabla ha sido creada exitosamente.<br>");
}else{
    print("<br> La tabla ya existe.<br>");
}

if ($conexion_DB -> query($sql_tabla2) == TRUE) {
    print("<br> La tabla ha sido creada exitosamente.<br>");
}else{
    print("<br> La tabla ya existe.<br>");
}

if ($conexion_DB -> query($sql_tabla3) == TRUE) {
    print("<br> La tabla ha sido creada exitosamente.<br>");
}else{
    print("<br> La tabla ya existe.<br>");
}

if ($conexion_DB -> query($sql_tabla4) == TRUE) {
    print("<br> La tabla ha sido creada exitosamente.<br>");
}else{
    print("<br> La tabla ya existe.<br>");
}

if ($conexion_DB -> query($sql_tabla5) == TRUE) {
    print("<br> La tabla ha sido creada exitosamente.<br>");
}else{
    print("<br> La tabla ya existe.<br>");
}

if ($respuesta != "-") {
    $insertar_datos = "INSERT INTO Cliente (Nombre, Apellidos, Email, Passwd, Telefono, Pais, Direccion, Imagen) 
                       VALUES ('$Nombre', '$Apellidos', '$Email', '$PasswdHashed', '$Telefono', '$Pais', '$Direccion', '$respuesta')";
} else {
    $insertar_datos = "INSERT INTO Cliente (Nombre, Apellidos, Email, Passwd, Telefono, Pais, Direccion) 
                       VALUES ('$Nombre', '$Apellidos', '$Email', '$PasswdHashed', '$Telefono', '$Pais', '$Direccion')";
}

if ($conexion_DB->query($insertar_datos) === TRUE) {
    echo "<br>Los datos se han insertado correctamente.<br>";
} else {
    echo "<br>Error al insertar los datos: " . $conexion_DB->error . "<br>";
}

$conexion_DB->close();
header("Location: login.php");
exit();
