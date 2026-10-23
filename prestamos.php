<?php
require_once 'database.php';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_prestamo'])) {
    $responsable = $_POST['responsable'];
    $fecha_p = $_POST['fecha_prestamo'];
    $fecha_dev = $_POST['fecha_prevista_devolucion'];
    $m_prestadas = intval($_POST['cant_mesas_prestadas']);
    $s_prestadas = intval($_POST['cant_sillas_prestadas']);
    $obs = $_POST['observaciones'];

    $inv = $pdo->query("SELECT tipo, cantidad_disponible FROM inventario_recursos")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if ($m_prestadas <= ($inv['mesa'] ?? 0) && $s_prestadas <= ($inv['silla'] ?? 0)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO prestamos (usuario_id, responsable, fecha_prestamo, fecha_prevista_devolucion, cant_mesas_prestadas, cant_sillas_prestadas, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?, 'Activo', ?)");
            $stmt->execute([$_SESSION['user_id'], $responsable, $fecha_p, $fecha_dev, $m_prestadas, $s_prestadas, $obs]);

            $pdo->prepare("UPDATE inventario_recursos SET cantidad_disponible = cantidad_disponible - ?, cantidad_prestada = cantidad_prestada + ? WHERE tipo = 'mesa'")->execute([$m_prestadas, $m_prestadas]);
            $pdo->prepare("UPDATE inventario_recursos SET cantidad_disponible = cantidad_disponible - ?, cantidad_prestada = cantidad_prestada + ? WHERE tipo = 'silla'")->execute([$s_prestadas, $s_prestadas]);

            $pdo->prepare("INSERT INTO historial_movimientos (usuario_id, tipo_operacion, recurso_afectado, cantidad, estado_anterior, estado_posterior) VALUES (?, 'Préstamo', 'Mesa/Silla', ?, 'Disponible', 'Prestado')")->execute([$_SESSION['user_id'], $m_prestadas + $s_prestadas]);

            $pdo->commit();
            $mensaje = "<div class='alert alert-success'>Préstamo registrado exitosamente.</div>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Error: No hay suficiente inventario disponible.</div>";
    }
}

$prestamos = $pdo->query("SELECT p.*, u.nombre as usuario FROM prestamos p JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.id DESC")->fetchAll();
$inv_disp = $pdo->query("SELECT tipo, cantidad_disponible FROM inventario_recursos")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Préstamos - Mobiliario</title>
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
                    <li class="nav-item"><a class="nav-link" href="inventario.php">Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservas.php">Reservas</a></li>
                    <li class="nav-item"><a class="nav-link active" href="prestamos.php">Préstamos</a></li>
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
        <h2 class="fw-bold text-primary"><i class="fa-solid fa-handshake"></i> Módulo de Préstamos</h2>
        <p class="text-muted">Registro y control de entrega de mesas y sillas.</p>
    </div>
</div>

<?= $mensaje ?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm bg-white">
            <div class="card-header bg-dark text-white"><h5 class="mb-0">Nuevo Préstamo</h5></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Persona Responsable</label>
                        <input type="text" name="responsable" class="form-control" required placeholder="Nombre completo">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Préstamo</label>
                        <input type="date" name="fecha_prestamo" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Prevista Devolución</label>
                        <input type="date" name="fecha_prevista_devolucion" class="form-control" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mesas a Prestar (Disp: <?= $inv_disp['mesa'] ?? 0 ?>)</label>
                        <input type="number" name="cant_mesas_prestadas" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sillas a Prestar (Disp: <?= $inv_disp['silla'] ?? 0 ?>)</label>
                        <input type="number" name="cant_sillas_prestadas" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" name="registrar_prestamo" class="btn btn-primary w-100"><i class="fa-solid fa-check"></i> Registrar Préstamo</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white"><h5 class="mb-0">Préstamos Registrados</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Responsable</th>
                            <th>Fechas</th>
                            <th>Recursos</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prestamos)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No hay préstamos activos.</td></tr>
                        <?php else: foreach ($prestamos as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><strong><?= htmlspecialchars($p['responsable']) ?></strong><br><small class="text-muted">Reg: <?= htmlspecialchars($p['usuario']) ?></small></td>
                                <td class="small">Del: <?= $p['fecha_prestamo'] ?><br>Al: <?= $p['fecha_prevista_devolucion'] ?></td>
                                <td><span class="badge bg-primary">Mesas: <?= $p['cant_mesas_prestadas'] ?></span><br><span class="badge bg-secondary">Sillas: <?= $p['cant_sillas_prestadas'] ?></span></td>
                                <td><span class="badge bg-success"><?= $p['estado'] ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    </main>
    <footer class="text-center py-4 text-muted border-top bg-white mt-5">
        <p class="mb-0">&copy; 2026 - Sistema Web de Gestión de Mesas y Sillas</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>