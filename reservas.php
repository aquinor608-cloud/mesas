<?php
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$error = '';

// Procesar devolución de reserva y control de daños
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'procesar_devolucion') {
    $reserva_id = intval($_POST['reserva_id']);
    $mesas_danadas = intval($_POST['mesas_danadas_dev'] ?? 0);
    $sillas_danadas = intval($_POST['sillas_danadas_dev'] ?? 0);
    $obs_dev = trim($_POST['observaciones_devolucion']);

    try {
        $pdo->beginTransaction();

        // Obtener datos de la reserva
        $stmt_res = $pdo->prepare("SELECT * FROM reservas WHERE id = ?");
        $stmt_res->execute([$reserva_id]);
        $reserva = $stmt_res->fetch();

        if (!$reserva) {
            throw new Exception("La reserva no existe.");
        }

        if (isset($reserva['estado_devolucion']) && $reserva['estado_devolucion'] === 'Devuelto') {
            throw new Exception("Esta reserva ya fue procesada y devuelta anteriormente.");
        }

        $mesas_res = intval($reserva['cantidad_mesas'] ?? $reserva['mesas'] ?? 0);
        $sillas_res = intval($reserva['cantidad_sillas'] ?? $reserva['sillas'] ?? 0);

        // Actualizar estado de la reserva
        $stmt_upd = $pdo->prepare("UPDATE reservas SET estado = 'Completado', estado_devolucion = 'Devuelto', mesas_danadas_dev = ?, sillas_danadas_dev = ?, observaciones_devolucion = ? WHERE id = ?");
        $stmt_upd->execute([$mesas_danadas, $sillas_danadas, $obs_dev, $reserva_id]);

        // Devolver recursos al inventario (restando los que venían dañados)
        $mesas_buenas = max(0, $mesas_res - $mesas_danadas);
        $sillas_buenas = max(0, $sillas_res - $sillas_danadas);

        if ($mesas_buenas > 0) {
            $pdo->prepare("UPDATE inventario_recursos SET cantidad_disponible = cantidad_disponible + ?, cantidad_reservada = cantidad_reservada - ? WHERE tipo = 'mesa'")->execute([$mesas_buenas, $mesas_res]);
        }
        if ($sillas_buenas > 0) {
            $pdo->prepare("UPDATE inventario_recursos SET cantidad_disponible = cantidad_disponible + ?, cantidad_reservada = cantidad_reservada - ? WHERE tipo = 'silla'")->execute([$sillas_buenas, $sillas_res]);
        }

        // Si hubo mesas o sillas dañadas, registrarlas automáticamente en la tabla inventario_danados
        $mes_actual = intval(date('n')); // Mes actual (1-12)
        if ($mesas_danadas > 0) {
            $pdo->prepare("INSERT INTO inventario_danados (tipo, cantidad, mes, anio, motivo) VALUES ('mesa', ?, ?, 2026, ?)")
                ->execute([$mesas_danadas, $mes_actual, "Devolución de reserva #$reserva_id: " . ($obs_dev ?: 'Daño detectado en entrega')]);
        }
        if ($sillas_danadas > 0) {
            $pdo->prepare("INSERT INTO inventario_danados (tipo, cantidad, mes, anio, motivo) VALUES ('silla', ?, ?, 2026, ?)")
                ->execute([$sillas_danadas, $mes_actual, "Devolución de reserva #$reserva_id: " . ($obs_dev ?: 'Daño detectado en entrega')]);
        }

        $pdo->commit();
        $mensaje = "¡Devolución procesada con éxito! Inventario actualizado y daños registrados correctamente.";

    } catch (Exception $ex) {
        $pdo->rollBack();
        $error = "Error al procesar la devolución: " . $ex->getMessage();
    }
}

// Obtener listado de reservas
$reservas = $pdo->query("SELECT * FROM reservas ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas - Control Mobiliario</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .navbar { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important; }
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-chair text-primary me-2"></i> Control Mobiliario</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto ms-lg-4">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventario.php">Inventario</a></li>
                    <li class="nav-item"><a class="nav-link active fw-semibold" href="reservas.php">Reservas</a></li>
                    <li class="nav-item"><a class="nav-link" href="prestamos.php">Préstamos</a></li>
                    <li class="nav-item"><a class="nav-link" href="danados.php">Reporte de Daños</a></li>
                </ul>
                <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Salir</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="mb-4">
            <h2 class="fw-bold text-dark"><i class="fa-solid fa-calendar-check text-primary me-2"></i> Gestión y Control de Reservas</h2>
            <p class="text-muted">Supervisa las agendas y procesa las devoluciones de mobiliario registrando daños si los hay.</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-4"><?= $mensaje ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-4"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card p-4">
            <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-list me-2"></i> Listado de Reservas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Usuario / Solicitante</th>
                            <th>Fecha / Horario</th>
                            <th>Mesas / Sillas</th>
                            <th>Estado</th>
                            <th>Devolución</th>
                            <th class="pe-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservas)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No hay reservas registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reservas as $r): 
                                $id_res = $r['id'];
                                $usuario = $r['usuario'] ?? $r['solicitante'] ?? 'N/A';
                                $mesas = $r['cantidad_mesas'] ?? $r['mesas'] ?? 0;
                                $sillas = $r['cantidad_sillas'] ?? $r['sillas'] ?? 0;
                                $estado_dev = $r['estado_devolucion'] ?? 'Pendiente';
                            ?>
                                <tr>
                                    <td class="ps-4 fw-bold">#<?= $id_res ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($usuario) ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($r['fecha'] ?? $r['fecha_reserva'] ?? 'N/A') ?></small></td>
                                    <td>
                                        <span class="badge bg-primary">M: <?= $mesas ?></span>
                                        <span class="badge bg-secondary">S: <?= $sillas ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $r['estado'] === 'Completado' ? 'success' : 'warning text-dark' ?>">
                                            <?= $r['estado'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $estado_dev === 'Devuelto' ? 'success' : 'secondary' ?>">
                                            <?= $estado_dev ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <?php if ($estado_dev !== 'Devuelto'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success fw-semibold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalDevolucion<?= $id_res ?>">
                                                <i class="fa-solid fa-arrow-rotate-left me-1"></i> Procesar Devolución
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fa-solid fa-check text-success me-1"></i> Finalizado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- MODAL PARA PROCESAR DEVOLUCIÓN Y DAÑOS -->
                                <div class="modal fade" id="modalDevolucion<?= $id_res ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content border-0 shadow">
                                            <form method="POST">
                                                <input type="hidden" name="accion" value="procesar_devolucion">
                                                <input type="hidden" name="reserva_id" value="<?= $id_res ?>">
                                                
                                                <div class="modal-header bg-dark text-white">
                                                    <h5 class="modal-title fw-bold">Procesar Devolución - Reserva #<?= $id_res ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <p class="text-muted small">Registra si el mobiliario regresó en perfectas condiciones o si hubo averías para descontarlas automáticamente del inventario y del reporte de daños.</p>
                                                    
                                                    <div class="alert alert-info py-2 small">
                                                        <strong>Alquilado:</strong> <?= $mesas ?> Mesas y <?= $sillas ?> Sillas
                                                    </div>

                                                    <div class="row g-3 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold small">Mesas Dañadas (a reportar)</label>
                                                            <input type="number" name="mesas_danadas_dev" class="form-control" min="0" max="<?= $mesas ?>" value="0" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold small">Sillas Dañadas (a reportar)</label>
                                                            <input type="number" name="sillas_danadas_dev" class="form-control" min="0" max="<?= $sillas ?>" value="0" required>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Observaciones o Motivo de los Daños</label>
                                                        <textarea name="observaciones_devolucion" class="form-control" rows="2" placeholder="Ej. 2 sillas llegaron rotas de una pata..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold"><i class="fa-solid fa-floppy-disk me-1"></i> Confirmar Devolución</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>