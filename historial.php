<?php
require_once 'database.php';
$movimientos = $pdo->query("SELECT h.*, u.nombre as usuario FROM historial_movimientos h JOIN usuarios u ON h.usuario_id = u.id ORDER BY h.id DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial y Auditoría - Mobiliario</title>
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
                    <li class="nav-item"><a class="nav-link" href="devoluciones.php">Devoluciones</a></li>
                    <li class="nav-item"><a class="nav-link active" href="historial.php">Historial</a></li>
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
        <h2 class="fw-bold text-primary"><i class="fa-solid fa-clock-rotate-left"></i> Historial y Auditoría de Movimientos</h2>
        <p class="text-muted">Registro detallado de operaciones realizadas en el sistema.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Tipo Operación</th>
                    <th>Recurso</th>
                    <th>Cantidad</th>
                    <th>Estado Anterior</th>
                    <th>Estado Posterior</th>
                    <th>Fecha y Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr><td colspan="8" class="text-center text-muted">No hay movimientos registrados.</td></tr>
                <?php else: foreach ($movimientos as $m): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= htmlspecialchars($m['usuario']) ?></td>
                        <td><span class="badge bg-primary"><?= $m['tipo_operacion'] ?></span></td>
                        <td><?= $m['recurso_afectado'] ?></td>
                        <td><strong><?= $m['cantidad'] ?></strong></td>
                        <td><span class="text-muted"><?= $m['estado_anterior'] ?></span></td>
                        <td><span class="text-success"><?= $m['estado_posterior'] ?></span></td>
                        <td class="small"><?= $m['fecha_hora'] ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

    </main>
    <footer class="text-center py-4 text-muted border-top bg-white mt-5">
        <p class="mb-0">&copy; 2026 - Sistema Web de Gestión de Mesas y Sillas</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>