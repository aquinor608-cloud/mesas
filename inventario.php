<?php
require_once 'database.php';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_inventario'])) {
    if ($_SESSION['rol'] !== 'admin') {
        $mensaje = "<div class='alert alert-danger'>Acceso denegado. Solo los administradores pueden modificar las cantidades de inventario.</div>";
    } else {
        $tipo = $_POST['tipo'];
        $nueva_total = intval($_POST['cantidad_total']);

        $stmt = $pdo->prepare("SELECT * FROM inventario_recursos WHERE tipo = ?");
        $stmt->execute([$tipo]);
        $recurso = $stmt->fetch();

        if ($recurso) {
            $en_uso = $recurso['cantidad_reservada'] + $recurso['cantidad_prestada'];
            if ($nueva_total >= $en_uso) {
                $nueva_disponible = $nueva_total - $en_uso;
                $update = $pdo->prepare("UPDATE inventario_recursos SET cantidad_total = ?, cantidad_disponible = ? WHERE tipo = ?");
                $update->execute([$nueva_total, $nueva_disponible, $tipo]);

                $hist = $pdo->prepare("INSERT INTO historial_movimientos (usuario_id, tipo_operacion, recurso_afectado, cantidad, estado_anterior, estado_posterior) VALUES (?, 'Modificación', ?, ?, ?, ?)");
                $hist->execute([$_SESSION['user_id'], ucfirst($tipo), $nueva_total, 'Total: ' . $recurso['cantidad_total'], 'Total: ' . $nueva_total]);

                $mensaje = "<div class='alert alert-success'>Inventario de {$tipo} actualizado correctamente.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error: La cantidad total no puede ser menor a los recursos en uso ($en_uso).</div>";
            }
        }
    }
}

$recursos = $pdo->query("SELECT * FROM inventario_recursos")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Mobiliario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="fa-solid fa-chair"></i> Control Mobiliario</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="inventario.php">Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservas.php">Reservas</a></li>
                    <li class="nav-item"><a class="nav-link" href="prestamos.php">Préstamos</a></li>
                    <li class="nav-item"><a class="nav-link" href="devoluciones.php">Devoluciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="historial.php">Historial</a></li>
                </ul>
                <div class="d-flex align-items-center text-white">
                    <span class="badge bg-<?= ($_SESSION['rol'] ?? '') === 'admin' ? 'danger' : 'primary' ?> me-2">
                        <?= ucfirst($_SESSION['rol'] ?? 'Usuario') ?>
                    </span>
                    <small class="me-3"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></small>
                    <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="container py-4">

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold text-primary"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Inventario</h2>
        <p class="text-muted">Administra el mobiliario disponible en el sistema.</p>
    </div>
</div>

<?= $mensaje ?>

<div class="row">
    <?php foreach ($recursos as $rec): ?>
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-capitalize"><i class="fa-solid fa-<?= $rec['tipo'] === 'mesa' ? 'table' : 'chair' ?>"></i> <?= $rec['tipo'] ?>s</h5>
                <span class="badge bg-success"><?= $rec['estado'] ?></span>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between"><span>Cantidad Total:</span> <strong><?= $rec['cantidad_total'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between text-success"><span>Disponibles:</span> <strong><?= $rec['cantidad_disponible'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between text-warning"><span>Reservadas:</span> <strong><?= $rec['cantidad_reservada'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between text-danger"><span>Prestadas:</span> <strong><?= $rec['cantidad_prestada'] ?></strong></li>
                </ul>

                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <form method="POST" class="row g-2 align-items-center">
                    <input type="hidden" name="tipo" value="<?= $rec['tipo'] ?>">
                    <div class="col-8">
                        <input type="number" name="cantidad_total" class="form-control" value="<?= $rec['cantidad_total'] ?>" min="0" required>
                    </div>
                    <div class="col-4">
                        <button type="submit" name="actualizar_inventario" class="btn btn-primary w-100"><i class="fa-solid fa-save"></i> Actualizar</button>
                    </div>
                </form>
                <?php else: ?>
                    <p class="small text-muted mb-0"><i class="fa-solid fa-lock"></i> Solo administradores pueden ajustar el stock total.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

    </main>
    <footer class="text-center py-4 text-muted border-top bg-white mt-5">
        <p class="mb-0">&copy; 2026 - Sistema Web de Gestión de Mesas y Sillas</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>