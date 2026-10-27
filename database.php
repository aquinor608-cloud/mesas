<?php
// database.php - Extracción automática y segura desde Neon
$connectionString = "postgresql://neondb_owner:npg_aJFE5hVbs8qn@ep-crimson-bird-b5fytpgz-pooler.c-7.us-east-2.aws.neon.tech/neondb?sslmode=require&channel_binding=require";

$url = parse_url($connectionString);
$host = $url["host"];
$port = $url["port"] ?? "5432";
$db   = ltrim($url["path"], "/");
$user = $url["user"];
$pass = $url["pass"];

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Si quieres verificar que ya conectó con éxito, descomenta la siguiente línea:
    // die("<h1 style='color: green;'>¡CONEXIÓN EXITOSA CON NEON!</h1>");

} catch (\PDOException $e) {
    die("<h2 style='color: red;'>ERROR DE PASSWORD:</h2><pre>" . $e->getMessage() . "</pre>");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>