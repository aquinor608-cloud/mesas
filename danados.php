<?php
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$error = '';

// Procesar reporte de daño
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_dano') {
    $tipo = $_POST['tipo'];
    $cantidad = intval($_POST['cantidad']);
    $mes = intval($_POST['mes']);
    $motivo = trim($_POST['motivo']);

    if ($cantidad <= 0 || $mes < 1 || $mes > 12) {
        $error = "Por favor, ingresa una cantidad y un mes válidos.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO inventario_danados (tipo, cantidad, mes, anio, motivo) VALUES (?, ?, ?, 2026, ?)");
        if ($stmt->execute([$tipo, $cantidad, $mes, $motivo])) {
            $mensaje = "¡Reporte de mobiliario dañado registrado correctamente!";
        } else {
            $error = "No se pudo registrar la información.";
        }
    }
}

// Meses de Enero a Octubre
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre'
];

// Consultar totales por mes
$reporte_meses = [];
foreach ($meses as $num => $nombre) {
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN tipo = 'mesa' THEN cantidad ELSE 0 END) as mesas_danadas,
        SUM(CASE WHEN tipo = 'silla' THEN cantidad ELSE 0 END) as sillas_danadas
        FROM inventario_danados WHERE mes = ? AND anio = 2026");
    $stmt->execute([$num]);
    $res = $stmt->fetch();
    $reporte_meses[$num] = [
        'nombre' => $nombre,
        'mesas' => $res['mesas_danadas'] ?? 0,
        'sillas' => $res['sillas_danadas'] ?? 0
    ];
}

$historial_danados = $pdo->query("SELECT * FROM inventario_danados ORDER BY mes DESC, fecha_registro DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Daños - Control Mobiliario</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f6f9; color: #2d3748; }
        .navbar { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.04); transition: transform 0.2s ease; }
        .card:hover { transform: translateY(-2px); }
        .card-header-custom { background: #ffffff; border-bottom: 1px solid #edf2f7; border-radius: 12px 12px 0 0 !important; padding: 1.2rem; }
        .badge-soft-danger { background-color: #fee2e2; color: #dc2626; font-weight: 600; padding: 4px 10px; border-radius: 6px; }
        .badge-soft-warning { background-color: #fef3c7; color: #d97706; font-weight: 600; padding: 4px 10px; border-radius: 6px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <i class="fa-solid fa-chair text-primary fs-4 me-2"></i> Control Mobiliario
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto ms-lg-4">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventario.php">Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservas.php">Reservas</a></li>
                    <li class="nav-item"><a class="nav-link" href="prestamos.php">Préstamos</a></li>
                    <li class="nav-item"><a class="nav-link active fw-semibold" href="danados.php">Reporte de Daños</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2 text-white">
                    <span class="badge bg-<?= ($_SESSION['rol'] ?? '') === 'admin' ? 'danger' : 'primary' ?> px-3 py-2 rounded-pill">
                        <?= ucfirst($_SESSION['rol'] ?? 'Usuario') ?>
                    </span>
                    <small class="fw-semibold text-light me-2"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></small>
                    <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="mb-4">
            <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-triangle-exclamation text-danger"></i> Control de Mobiliario Dañado</h2>
            <p class="text-muted mb-0">Monitoreo mensual de averías y bajas (Enero - Octubre 2026)</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-4"><?= $mensaje ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-4"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <!-- Formulario de Registro -->
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-plus-circle text-danger me-2"></i> Reportar Daño</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <input type="hidden" name="accion" value="registrar_dano">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tipo de Mobiliario</label>
                                <select name="tipo" class="form-select py-2" required>
                                    <option value="mesa">Mesa</option>
                                    <option value="silla">Silla</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Cantidad Dañada</label>
                                <input type="number" name="cantidad" class="form-control py-2" min="1" required placeholder="Ej. 2">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mes de Registro</label>
                                <select name="mes" class="form-select py-2" required>
                                    <?php foreach ($meses as $num => $nombre): ?>
                                        <option value="<?= $num ?>" <?= $num == date('n') ? 'selected' : '' ?>><?= $nombre ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Motivo</label>
                                <textarea name="motivo" class="form-control py-2" rows="3" placeholder="Ej. Patas desoldadas"></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100 fw-semibold py-2"><i class="fa-solid fa-floppy-disk me-2"></i> Registrar Reporte</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Módulo de Tarjetas por Mes (Enero - Octubre) -->
            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calendar-days text-primary me-2"></i> Reporte Mensual (Enero - Octubre 2026)</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <?php foreach ($reporte_meses as $num => $datos): ?>
                                <div class="col-md-6 col-xl-4">
                                    <div class="border rounded-3 p-3 bg-white">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-dark"><?= $datos['nombre'] ?></span>
                                            <small class="text-muted">2026</small>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted"><i class="fa-solid fa-table me-1"></i> Mesas:</small>
                                            <span class="badge-soft-danger"><?= $datos['mesas'] ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted"><i class="fa-solid fa-chair me-1"></i> Sillas:</small>
                                            <span class="badge-soft-warning"><?= $datos['sillas'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla Historial -->
        <div class="card shadow-sm">
            <div class="card-header-custom">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list me-2"></i> Historial de Reportes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tipo</th>
                                <th>Cantidad</th>
                                <th>Mes</th>
                                <th>Motivo</th>
                                <th class="pe-4">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historial_danados)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No hay daños registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($historial_danados as $h): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-capitalize"><?= $h['tipo'] ?></td>
                                        <td><span class="badge bg-danger rounded-pill px-3"><?= $h['cantidad'] ?></span></td>
                                        <td><span class="fw-semibold text-dark"><?= $meses[$h['mes']] ?? 'N/A' ?></span></td>
                                        <td class="text-muted"><?= htmlspecialchars($h['motivo'] ?: 'Sin especificar') ?></td>
                                        <td class="pe-4 text-muted"><small><?= date('d/m/Y h:i A', strtotime($h['fecha_registro'])) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>