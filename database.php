<?php
// database.php - Conexión directa mediante DSN seguro
$host = 'ep-crimson-bird-b5fytrpgz-pooler.c-7.us-east-2.aws.neon.tech';
$db   = 'neondb';
$user = 'neondb_owner';
$pass = 'npg_aJFE5hVbs8qn'; // Dale clic a "Show password" en la imagen y cópiala aquí
$port = '5432';

try {
    // Agregamos channel_binding=prefer o removemos restricciones si da conflicto con PDO antiguo
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Si estabas probando el mensaje de éxito, puedes dejar esto o quitarlo para que cargue la app normal:
    // echo "<h1 style='color: green;'>¡CONEXIÓN EXITOSA CON NEON!</h1>";

} catch (\PDOException $e) {
    die("<h2 style='color: red;'>ERROR DE PASSWORD:</h2><pre>" . $e->getMessage() . "</pre>");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>