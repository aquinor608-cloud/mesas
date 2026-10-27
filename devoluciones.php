<?php
require_once 'database.php';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_devolucion'])) {
    $prestamo_id = intval($_POST['prestamo_id']);
    $m_devueltas = intval($_POST['mesas_devueltas']);
    $s_devueltas = intval($_POST['sillas_devueltas']);
    $obs = $_POST['observaciones'];

    $stmt = $pdo->prepare("SELECT * FROM prestamos WHERE id = ? AND estado IN ('Activo', 'Parcialmente devuelto')");
    $stmt->execute([$prestamo_id]);
    $prestamo = $stmt->fetch();

    if ($prestamo) {
        $dev_prev = $pdo->prepare("SELECT /* refresh_cache */ SUM(cant_mesas_devueltas) as mesas_tot, SUM(cant_sillas_devueltas) as sillas_tot FROM detalles_devoluciones WHERE prestamo_id = ?");
        $dev_prev->execute([$prestamo_id]);
        $acumulado = $dev_prev->fetch();

        $mesas_ya_devueltas = $acumulado['mesas_tot'] ?? 0;
        $sillas_ya_devueltas = $acumulado['sillas_tot'] ?? 0;

        $mesas_pendientes = $prestamo['cant_mesas_prestadas'] - $mesas_ya_devueltas;
        $sillas_pendientes = $prestamo['cant_sillas_prestadas'] - $sillas_ya_devueltas;

        if ($m_devueltas <= $mesas_pendientes && $s_devueltas <= $sillas_pendientes) {
            try {
                $pdo->beginTransaction();

                $stmt_det = $pdo->prepare("INSERT INTO detalles_devoluciones (prestamo_id, cant_mesas_devueltas, cant_sillas_devueltas, observaciones) VALUES (?, ?, ?, ?)");
                $stmt_det->execute([$prestamo_id, $m_devueltas, $s_devueltas, $obs]);

                $pdo->prepare("UPDATE inventario_recursos SET cantidad_disponible = cantidad_disponible + ?, cantidad_prestada = cantidad_prestada - ? WHERE tipo = 'mesa'")->execute([$m_devueltas, $m_devueltas]);
                $pdo->prepare("UPDATE inventario_recursos SET cantidad_disponible = cantidad_disponible + ?, cantidad_prestada = cantidad_prestada - ? WHERE tipo = 'silla'")->execute([$s_devueltas, $s_devueltas]);

                $nuevo_mesas_devueltas = $mesas_ya_devueltas + $m_devueltas;
                $nuevo_sillas_devueltas = $sillas_ya_devueltas + $s_devueltas;

                if ($nuevo_mesas_devueltas == $prestamo['cant_mesas_prestadas'] && $nuevo_sillas_devueltas == $prestamo['cant_sillas_prestadas']) {
                    $nuevo_estado = 'Devuelto';
                } else {
                    $nuevo_estado = 'Parcialmente devuelto';
                }

                $pdo->prepare("UPDATE prestamos SET estado = ? WHERE id = ?")->execute([$nuevo_estado, $prestamo_id]);

                $pdo->prepare("INSERT INTO historial_movimientos (usuario_id, tipo_operacion, recurso_afectado, cantidad, estado_anterior, estado_posterior) VALUES (?, 'Devolución', 'Mesa/Silla', ?, 'Prestado', ?)")->execute([$_SESSION['user_id'], $m_devueltas + $s_devueltas, $nuevo_estado]);

                $pdo->commit();
                $mensaje = "<div class='alert alert-success'>Devolución procesada correctamente.</div>";
            } catch (Exception $e) {
                $pdo->rollBack();
                $mensaje = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>Error: Las cantidades devueltas superan los saldos pendientes.</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Préstamo no encontrado o finalizado.</div>";
    }
}

$prestamos = $pdo->query("SELECT /* refresh_cache */ * FROM prestamos");
$historial_devs = $pdo->query("SELECT d.*, p.responsable FROM detalles_devoluciones d JOIN prestamos p ON d.prestamo_id = p.id ORDER BY d.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Devoluciones - Mobiliario</title>
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
                    <li class="nav-item"><a class="nav-link" href="prestamos.php">Préstamos</a></li>
                    <li class="nav-item"><a class="nav-link active" href="devoluciones.php">Devoluciones</a></li>
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
        <h2 class="fw-bold text-primary"><i class="fa-solid fa-rotate-left"></i> Módulo de Devoluciones</h2>
        <p class="text-muted">Procesa devoluciones totales y parciales.</p>
    </div>
</div>

<?= $mensaje ?>

<div class="row mb-4">
    <div class="col-md-5">
        <div class="card shadow-sm bg-white">
            <div class="card-header bg-success text-white"><h5 class="mb-0">Registrar Devolución</h5></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Préstamo Activo</label>
                        <select name="prestamo_id" class="form-select" required>
                            <option value="">-- Seleccione Préstamo --</option>
                            <?php foreach ($prestamos_activos as $pa): ?>
                                <option value="<?= $pa['id'] ?>">ID: <?= $pa['id'] ?> - <?= htmlspecialchars($pa['responsable']) ?> (M: <?= $pa['cant_mesas_prestadas'] ?>, S: <?= $pa['cant_sillas_prestadas'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mesas a Devolver</label>
                        <input type="number" name="mesas_devueltas" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sillas a Devolver</label>
                        <input type="number" name="sillas_devueltas" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2" placeholder="Estado del mobiliario devuelto..."></textarea>
                    </div>
                    <button type="submit" name="registrar_devolucion" class="btn btn-success w-100"><i class="fa-solid fa-arrow-rotate-left"></i> Procesar Devolución</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white"><h5 class="mb-0">Historial de Devoluciones Recientes</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID Préstamo</th>
                            <th>Responsable</th>
                            <th>Devueltos</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historial_devs)): ?>
                            <tr><td colspan="4" class="text-center text-muted">No hay devoluciones registradas.</td></tr>
                        <?php else: foreach ($historial_devs as $hd): ?>
                            <tr>
                                <td><?= $hd['prestamo_id'] ?></td>
                                <td><strong><?= htmlspecialchars($hd['responsable']) ?></strong></td>
                                <td><span class="badge bg-success">M: <?= $hd['cant_mesas_devueltas'] ?></span> <span class="badge bg-info">S: <?= $hd['cant_sillas_devueltas'] ?></span></td>
                                <td class="small"><?= $hd['fecha_devolucion_real'] ?></td>
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