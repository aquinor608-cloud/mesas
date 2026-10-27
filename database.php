<?php
// Prueba directa temporal en database.php
$host = 'ep-crimson-bird-b5fytrpgz-pooler.c-7.us-east-2.aws.neon.tech';
$db   = 'neondb';
$user = 'neondb_owner';
$pass = 'npg_ZST6lnh3VKdi'; 
$port = '5432';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    die("<h1 style='color: green;'>¡CONEXIÓN EXITOSA CON NEON! El password funciona perfectamente.</h1>");
} catch (\PDOException $e) {
    die("<h2 style='color: red;'>ERROR DE PASSWORD:</h2><pre>" . $e->getMessage() . "</pre>");
}
?>