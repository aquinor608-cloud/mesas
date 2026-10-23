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
                // Verificar si ya existe el usuario o correo
                $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR usuario = ?");
                $stmt_check->execute([$email_o_usuario, $email_o_usuario]);

                if ($stmt_check->fetch()) {
                    $error = "El correo o usuario ya se encuentra registrado.";
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Enviamos el correo/usuario tanto a 'usuario' como a 'email' para cumplir con cualquier restricción NOT NULL
                    $stmt_ins = $pdo->prepare("INSERT INTO usuarios (usuario, email, nombre, password, rol) VALUES (?, ?, ?, ?, 'admin')");
                    $stmt_ins->execute([$email_o_usuario, $email_o_usuario, $nombre, $password_hash]);

                    $mensaje = "¡Cuenta creada exitosamente! Ya puedes iniciar sesión.";
                }
            } catch (PDOException $e) {
                $error = "Error al crear la cuenta: " . $e->getMessage();
            }
        }
    } else {
        // Inicio de sesión
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