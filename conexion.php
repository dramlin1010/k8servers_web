<?php
$servername = "mariadb-host-svc.default.svc.cluster.local";
$username = "daniel";
$password = "Kt3xa6RqSAgdpskCZyuWfX";
$dbname = "k8servers";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>