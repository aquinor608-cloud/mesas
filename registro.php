<?php
require_once 'database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingresa un correo electrónico válido.";
    } else {
        // Verificar si el correo ya existe en la base de datos
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Este correo ya está registrado. Por favor, inicia sesión.";
        } else {
            // Asignación de rol: únicamente admin123@gmail.com es admin, todos los demás son operador
            $rol = ($email === 'admin123@gmail.com') ? 'admin' : 'operador';
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
            if ($insert->execute([$nombre, $email, $password_hash, $rol])) {
                $exito = "¡Cuenta creada exitosamente! Ahora puedes iniciar sesión con tu correo.";
            } else {
                $error = "Ocurrió un error al guardar la cuenta en la base de datos.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Control Mobiliario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-body p-4 text-center">
                    <h3 class="fw-bold mb-2 text-success"><i class="fa-solid fa-user-plus"></i> Registrar Nueva Cuenta</h3>
                    <p class="text-muted small mb-4">Crea tu usuario para acceder al sistema</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 small"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if ($exito): ?>
                        <div class="alert alert-success py-2 small"><?= $exito ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3 text-start">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej. Carlos Aquino">
                        </div>
                        <div class="mb-3 text-start">
                            <label class="form-label">Correo Electrónico (Gmail u otro)</label>
                            <input type="email" name="email" class="form-control" required placeholder="ejemplo@gmail.com">
                        </div>
                        <div class="mb-3 text-start">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required placeholder="••••••">
                        </div>
                        <button type="submit" class="btn btn-success w-100 mb-3"><i class="fa-solid fa-check"></i> Registrar Cuenta</button>
                    </form>

                    <div class="text-muted small">
                        ¿Ya tienes cuenta? <a href="login.php" class="text-decoration-none fw-bold">Inicia sesión aquí</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>