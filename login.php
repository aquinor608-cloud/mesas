<?php
require_once 'database.php';

$error = '';
$mensaje = '';

// Procesar inicio de sesión o registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'login';
    $email_o_usuario = trim($_POST['email'] ?? $_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');

    if ($accion === 'registro') {
        if (empty($email_o_usuario) || empty($password)) {
            $error = "Por favor complete todos los campos obligatorios.";
        } else {
            try {
                $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR usuario = ?");
                $stmt_check->execute([$email_o_usuario, $email_o_usuario]);

                if ($stmt_check->fetch()) {
                    $error = "El correo o usuario ya se encuentra registrado.";
                } else {
                    // Definir el rol automáticamente según el correo electrónico
                    $rol_asignado = ($email_o_usuario === 'aquinor608@gmail.com') ? 'admin' : 'operador';

                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_ins = $pdo->prepare("INSERT INTO usuarios (usuario, email, nombre, password, rol) VALUES (?, ?, ?, ?, ?)");
                    $stmt_ins->execute([$email_o_usuario, $email_o_usuario, $nombre, $password_hash, $rol_asignado]);

                    $mensaje = "¡Cuenta creada exitosamente! Ya puedes iniciar sesión.";
                }
            } catch (PDOException $e) {
                $error = "Error al crear la cuenta: " . $e->getMessage();
            }
        }
    } else {
        if (empty($email_o_usuario) || empty($password)) {
            $error = "Ingrese su correo/usuario y contraseña.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? OR usuario = ?");
            $stmt->execute([$email_o_usuario, $email_o_usuario]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['usuario'] = $user['usuario'] ?? $user['email'];
                $_SESSION['nombre'] = $user['nombre'] ?? $user['usuario'];
                $_SESSION['rol'] = $user['rol'];

                header('Location: index.php');
                exit;
            } else {
                $error = "Credenciales incorrectas. Verifique su correo/usuario y contraseña.";
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
    <title>Acceso al Sistema - Control Mobiliario</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); background: #ffffff; width: 100%; max-width: 420px; }
    </style>
</head>
<body>

<div class="container py-5 d-flex justify-content-center">
    <div class="card card-login p-4">
        <div class="text-center mb-4">
            <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-inline-block mb-2">
                <i class="fa-solid fa-chair fa-2x"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">Control Mobiliario</h4>
            <p class="text-muted small">Plataforma de Inventario y Alquileres</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success border-0 small"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Pestañas Inicio / Registro -->
        <ul class="nav nav-pills nav-justified mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold btn-sm" id="tab-login" data-bs-toggle="pill" data-bs-target="#pills-login" type="button">Iniciar Sesión</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold btn-sm" id="tab-registro" data-bs-toggle="pill" data-bs-target="#pills-registro" type="button">Crear Cuenta</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- Formulario Login -->
            <div class="tab-pane fade show active" id="pills-login" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="accion" value="login">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Correo o Usuario</label>
                        <input type="text" name="email" class="form-control" required placeholder="admin@correo.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contraseña</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Ingresar al Sistema</button>
                </form>
            </div>

            <!-- Formulario Registro -->
            <div class="tab-pane fade" id="pills-registro" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="accion" value="registro">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej. Juan Pérez">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" required placeholder="correo@ejemplo.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contraseña</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-semibold py-2">Registrarme</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>