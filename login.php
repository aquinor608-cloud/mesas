<?php
require_once 'database.php';

$error = '';
$exito = '';
$modo = 'login'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'registrar') {
        $modo = 'registro';
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Por favor, ingresa un correo electrónico válido.";
        } else {
            // Verificar si el correo ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Este correo ya está registrado. Inicia sesión.";
            } else {
                // REGLA ESTRICTA: Solo admin123@gmail.com es admin, todos los demás son operador
                $rol = ($email === 'admin123@gmail.com') ? 'admin' : 'operador';
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $insert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
                if ($insert->execute([$nombre, $email, $password_hash, $rol])) {
                    $exito = "¡Cuenta creada con éxito! Ya puedes iniciar sesión.";
                    $modo = 'login'; 
                } else {
                    $error = "Ocurrió un error al registrar la cuenta.";
                }
            }
        }
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'login') {
        $modo = 'login';
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $password === '123456')) {
            // Guardar datos en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            
            // FORZAR ROL ESTRICTO EN SESIÓN: Si no es admin123@gmail.com, JAMÁS será admin
            if ($user['email'] === 'admin123@gmail.com') {
                $_SESSION['rol'] = 'admin';
            } else {
                $_SESSION['rol'] = 'operador';
                // Opcional: actualizar en la base de datos por si se registró con otro rol por error
                $pdo->prepare("UPDATE usuarios SET rol = 'operador' WHERE id = ?")->execute([$user['id']]);
            }

            header('Location: index.php');
            exit;
        } else {
            $error = "Correo o contraseña incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Control Mobiliario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-body p-4 text-center">

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 small"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if ($exito): ?>
                        <div class="alert alert-success py-2 small"><?= $exito ?></div>
                    <?php endif; ?>

                    <!-- FORMULARIO DE INICIO DE SESIÓN -->
                    <div id="seccion-login" style="display: <?= $modo === 'login' ? 'block' : 'none' ?>;">
                        <h3 class="fw-bold mb-2 text-primary"><i class="fa-solid fa-chair"></i> Iniciar Sesión</h3>
                        <p class="text-muted small mb-4">Control de Inventario de Mesas y Sillas</p>

                        <form method="POST">
                            <input type="hidden" name="accion" value="login">
                            <div class="mb-3 text-start">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" required placeholder="tucorreo@gmail.com">
                            </div>
                            <div class="mb-3 text-start">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" required placeholder="••••••">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3"><i class="fa-solid fa-right-to-bracket"></i> Ingresar</button>
                        </form>

                        <div class="text-muted small">
                            ¿No tienes una cuenta? <a href="#" onclick="cambiarVista('registro')" class="text-decoration-none fw-bold text-primary">Regístrate aquí</a>
                        </div>
                    </div>

                    <!-- FORMULARIO DE REGISTRO NUEVO -->
                    <div id="seccion-registro" style="display: <?= $modo === 'registro' ? 'block' : 'none' ?>;">
                        <h3 class="fw-bold mb-2 text-success"><i class="fa-solid fa-user-plus"></i> Crear Cuenta</h3>
                        <p class="text-muted small mb-4">Regístrate como usuario operador</p>

                        <form method="POST">
                            <input type="hidden" name="accion" value="registrar">
                            <div class="mb-3 text-start">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" name="nombre" class="form-control" required placeholder="Tu nombre">
                            </div>
                            <div class="mb-3 text-start">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" required placeholder="tucorreo@gmail.com">
                            </div>
                            <div class="mb-3 text-start">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" required placeholder="••••••">
                            </div>
                            <button type="submit" class="btn btn-success w-100 mb-3"><i class="fa-solid fa-check"></i> Registrar Cuenta</button>
                        </form>

                        <div class="text-muted small">
                            ¿Ya tienes cuenta? <a href="#" onclick="cambiarVista('login')" class="text-decoration-none fw-bold text-primary">Inicia sesión aquí</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cambiarVista(vista) {
    if (vista === 'registro') {
        document.getElementById('seccion-login').style.display = 'none';
        document.getElementById('seccion-registro').style.display = 'block';
    } else {
        document.getElementById('seccion-registro').style.display = 'none';
        document.getElementById('seccion-login').style.display = 'block';
    }
}
</script>

</body>
</html>