<?php
$servername = "localhost";
$username = "daniel";
$password = "Kt3xa6RqSAgdpskCZyuWfX";
$dbname = "k8servers";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>