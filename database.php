<?php
$host = 'localhost';
$dbname = 'control_mobiliario';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$script_actual = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $script_actual !== 'login.php') {
    header('Location: login.php');
    exit;
}
?>