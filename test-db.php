<?php
// test-db.php - Diagnóstico rápido de conexión a Neon
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'ep-crimson-bird-b5fytrpgz-pooler.c-7.us-east-2.aws.neon.tech';
$db   = 'neondb';
$user = 'neondb_owner';
$pass = 'npg_ZST6lnh3VKdi'; 
$port = '5432';

echo "<h2>Probando conexión a Neon...</h2>";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p style='color: green; font-weight: bold; font-size: 20px;'>¡CONEXIÓN EXITOSA! La base de datos respondió correctamente.</p>";
    
    // Probando una consulta simple
    $stmt = $pdo->query("SELECT version()");
    $row = $stmt->fetch();
    echo "<p>Versión de PostgreSQL: " . $row['version'] . "</p>";

} catch (\PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>ERROR DE CONEXIÓN:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>