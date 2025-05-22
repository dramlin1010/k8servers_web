<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    $_SESSION['error_message'] = "Acceso no autorizado. Por favor, inicia sesión.";
    header("Location: login.php");
    exit();
}

if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy();
    setcookie("session_token", "", time() - 3600, "/");
    $_SESSION['error_message'] = "Token de sesión inválido. Por favor, inicia sesión de nuevo.";
    header("Location: login.php");
    exit();
}

$clienteID = $_SESSION['ClienteID'];
$dominioBase = "k8servers.es";
$planHostingIDPredeterminado = "developer_pro";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error_message'] = "Método de solicitud no válido.";
    header("Location: contratar_servicio.php");
    exit();
}

$subdominioElegido = isset($_POST['subdominio_elegido']) ? trim(strtolower($_POST['subdominio_elegido'])) : '';
$terminosAceptados = isset($_POST['terminos_servicio']);

if (!$terminosAceptados) {
    $_SESSION['error_message'] = "Debes aceptar los Términos y Condiciones del Servicio.";
    header("Location: contratar_servicio.php");
    exit();
}

if (empty($subdominioElegido)) {
    $_SESSION['error_message'] = "Debes elegir un nombre para tu subdominio.";
    header("Location: contratar_servicio.php");
    exit();
}

if (!preg_match('/^[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?$/', $subdominioElegido)) {
    $_SESSION['error_message'] = "El nombre del subdominio solo puede contener letras minúsculas, números y guiones. No puede empezar ni terminar con guion, ni tener guiones consecutivos.";
    header("Location: contratar_servicio.php");
    exit();
}
if (strlen($subdominioElegido) < 3 || strlen($subdominioElegido) > 30) {
     $_SESSION['error_message'] = "El nombre del subdominio debe tener entre 3 y 30 caracteres.";
    header("Location: contratar_servicio.php");
    exit();
}

$palabrasReservadas = ['www', 'ftp', 'mail', 'admin', 'blog', 'shop', 'test', 'dev', 'k8servers', 'panel', 'support', 'billing', 'api'];
if (in_array($subdominioElegido, $palabrasReservadas)) {
    $_SESSION['error_message'] = "El nombre de subdominio elegido ('" . htmlspecialchars($subdominioElegido) . "') no está permitido.";
    header("Location: contratar_servicio.php");
    exit();
}

$dominioCompleto = $subdominioElegido . "." . $dominioBase;

$conn->begin_transaction();

try {
    $stmt_get_plan = $conn->prepare("SELECT Precio, NombrePlan FROM Plan_Hosting WHERE PlanHostingID = ? AND Activo = TRUE");
    $stmt_get_plan->bind_param('s', $planHostingIDPredeterminado);
    $stmt_get_plan->execute();
    $result_plan = $stmt_get_plan->get_result();
    $planDetails = $result_plan->fetch_assoc();
    $stmt_get_plan->close();

    if (!$planDetails) {
        $_SESSION['error_message'] = "El plan de hosting seleccionado no está disponible.";
        $conn->rollback();
        header("Location: contratar_servicio.php");
        exit();
    }
    $precioPlan = $planDetails['Precio'];
    $nombrePlan = $planDetails['NombrePlan'];

    $sql_check_servicio = "SELECT SitioID FROM SitioWeb WHERE ClienteID = ? AND PlanHostingID = ?";
    $stmt_check = $conn->prepare($sql_check_servicio);
    $stmt_check->bind_param('is', $clienteID, $planHostingIDPredeterminado);
    $stmt_check->execute();
    $result_check_servicio = $stmt_check->get_result();
    if ($result_check_servicio->num_rows > 0) {
        $_SESSION['error_message'] = "Ya tienes un servicio de hosting activo con este plan.";
        $stmt_check->close();
        $conn->rollback();
        header("Location: mis_sitios.php");
        exit();
    }
    $stmt_check->close();

    $sql_check_subdominio = "SELECT SitioID FROM SitioWeb WHERE DominioCompleto = ?";
    $stmt_check_sub = $conn->prepare($sql_check_subdominio);
    $stmt_check_sub->bind_param('s', $dominioCompleto);
    $stmt_check_sub->execute();
    $result_check_subdominio = $stmt_check_sub->get_result();
    if ($result_check_subdominio->num_rows > 0) {
        $_SESSION['error_message'] = "El subdominio '" . htmlspecialchars($dominioCompleto) . "' ya está en uso. Por favor, elige otro.";
        $stmt_check_sub->close();
        $conn->rollback();
        header("Location: contratar_servicio.php");
        exit();
    }
    $stmt_check_sub->close();

    $estadoServicioInicial = 'pendiente_pago';
    $fechaContratacion = date('Y-m-d H:i:s');
    $fechaProximaRenovacion = date('Y-m-d', strtotime('+1 month'));

    $sql_insert_sitio = "INSERT INTO SitioWeb (ClienteID, PlanHostingID, SubdominioElegido, DominioCompleto, EstadoServicio, FechaContratacion, FechaProximaRenovacion) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_sitio = $conn->prepare($sql_insert_sitio);
    $stmt_insert_sitio->bind_param('issssss', $clienteID, $planHostingIDPredeterminado, $subdominioElegido, $dominioCompleto, $estadoServicioInicial, $fechaContratacion, $fechaProximaRenovacion);
    
    if (!$stmt_insert_sitio->execute()) {
        throw new Exception("Error al crear el registro del sitio: " . $stmt_insert_sitio->error);
    }
    $nuevoSitioID = $conn->insert_id;
    $stmt_insert_sitio->close();

    $descripcionFactura = "Activación " . $nombrePlan . " - " . $dominioCompleto;
    $estadoFactura = 'pendiente';
    $fechaEmisionFactura = date('Y-m-d');
    $fechaVencimientoFactura = date('Y-m-d', strtotime('+7 days'));

    $sql_insert_factura = "INSERT INTO Factura (ClienteID, SitioID, Descripcion, Monto, Estado, FechaEmision, FechaVencimiento) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_factura = $conn->prepare($sql_insert_factura);
    $stmt_insert_factura->bind_param('iisdsss', $clienteID, $nuevoSitioID, $descripcionFactura, $precioPlan, $estadoFactura, $fechaEmisionFactura, $fechaVencimientoFactura);

    if (!$stmt_insert_factura->execute()) {
        throw new Exception("Error al generar la factura: " . $stmt_insert_factura->error);
    }
    $nuevaFacturaID = $conn->insert_id;
    $stmt_insert_factura->close();

    $conn->commit();

    $_SESSION['success_message'] = "¡Servicio de hosting para '" . htmlspecialchars($dominioCompleto) . "' solicitado! Se ha generado una factura pendiente.";
    header("Location: facturas.php?highlight_factura=" . $nuevaFacturaID);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Ocurrió un error al procesar tu solicitud: " . $e->getMessage();
    header("Location: contratar_servicio.php");
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
