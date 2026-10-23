<?php
// database.php - Conexión a Neon (PostgreSQL)
$host = 'ep-crimson-bird-b5fytrpg-pooler.c-7.us-east-2.aws.neon.tech';
$db   = 'neondb';
$user = 'neondb_owner';
$pass = 'npg_gERZJ7UHp0qu'; // Haz clic en "Show password" en la imagen y ponla aquí
$port = '5432';

try {
    // Conexión PDO para PostgreSQL con SSL requerido por Neon
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

} catch (\PDOException $e) {
    die("Error de conexión a la base de datos en Neon: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>