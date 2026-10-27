<?php
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Consultar stock de recursos
$resultado = $pdo->query("SELECT /* refresh_cache */ * FROM inventario_recursos");
$recursos = [];
while ($row = $stmt->fetch()) {
    $recursos[$row['tipo']] = $row;
}

$mesas = $recursos['mesa'] ?? ['cantidad_total' => 20000, 'cantidad_disponible' => 20000, 'cantidad_prestada' => 0, 'cantidad_reservada' => 0];
$sillas = $recursos['silla'] ?? ['cantidad_total' => 30000, 'cantidad_disponible' => 30000, 'cantidad_prestada' => 0, 'cantidad_reservada' => 0];

// Calcular porcentajes de disponibilidad
$pct_mesas = $mesas['cantidad_total'] > 0 ? round(($mesas['cantidad_disponible'] / $mesas['cantidad_total']) * 100) : 0;
$pct_sillas = $sillas['cantidad_total'] > 0 ? round(($sillas['cantidad_disponible'] / $sillas['cantidad_total']) * 100) : 0;

// Consultas de estado
$res_activas = $pdo->query("SELECT COUNT(*) FROM reservas WHERE estado = 'Pendiente'")->fetchColumn();
$pres_activos_cnt = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado IN ('Activo', 'Parcialmente devuelto')")->fetchColumn();

// Verificar si existe la tabla de dañados
$total_danados = 0;
try {
    $total_danados = $pdo->query("SELECT IFNULL(SUM(cantidad), 0) FROM inventario_danados WHERE anio = 2026")->fetchColumn();
} catch (Exception $e) {
    $total_danados = 0;
}

// Consulta de préstamos activos
$prestamos_activos = $pdo->query("SELECT /* refresh_cache */ * FROM prestamos WHERE estado IN ('Activo', 'Parcialmente devuelto')");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard General - Control Mobiliario</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js para Gráficos Interactivos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #047857 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            --dark-gradient: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; transition: background-color 0.3s ease, color 0.3s ease; }
        .navbar { background: var(--dark-gradient) !important; box-shadow: 0 4px 25px rgba(0,0,0,0.1); }
        
        .dash-card { border: none; border-radius: 16px; background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.03); transition: all 0.3s ease; }
        .dash-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }

        .stat-banner { border-radius: 16px; color: white; padding: 1.5rem; position: relative; overflow: hidden; }
        .stat-banner .bg-icon { position: absolute; right: -10px; bottom: -15px; font-size: 6rem; opacity: 0.15; transform: rotate(-10deg); }

        /* MANTENER LOS COLORES VIVOS DE LOS BANNERS */
        .banner-blue { background: var(--primary-gradient) !important; }
        .banner-green { background: var(--success-gradient) !important; }
        .banner-orange { background: var(--warning-gradient) !important; }
        .banner-red { background: var(--danger-gradient) !important; }

        .btn-quick { border-radius: 12px; padding: 0.8rem 1.2rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s ease; }
        .btn-quick:hover { transform: scale(1.02); }

        .badge-soft-warning { background: #fef3c7; color: #b45309; font-weight: 600; }

        /* ESTILOS PARA MODO OSCURO (DARK MODE) */
        body.dark-mode {
            background-color: #0f172a !important;
            color: #f8fafc !important;
        }

        body.dark-mode .dash-card {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border: 1px solid #334155;
        }

        body.dark-mode h2, 
        body.dark-mode h5, 
        body.dark-mode .text-dark {
            color: #ffffff !important;
        }

        body.dark-mode .text-muted {
            color: #94a3b8 !important;
        }

        body.dark-mode .table {
            color: #f8fafc !important;
        }

        body.dark-mode .table-light {
            background-color: #334155 !important;
            color: #ffffff !important;
        }

        body.dark-mode .list-group-item {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }

        body.dark-mode .border-bottom, 
        body.dark-mode .border {
            border-color: #334155 !important;
        }
    </style>
</head>
<body>

    <!-- NAVBAR PREMIUM -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <div class="bg-primary bg-opacity-20 p-2 rounded-3 me-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-chair text-primary fs-5"></i>
                </div>
                <span>Control Mobiliario</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto ms-lg-4">
                    <li class="nav-item"><a class="nav-link active fw-semibold" href="index.php"><i class="fa-solid fa-chart-pie me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="fa-solid fa-boxes-stacked me-1"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="alquileres.php"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Alquileres</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservas.php"><i class="fa-solid fa-calendar-check me-1"></i> Reservas</a></li>
                    <li class="nav-item"><a class="nav-link" href="prestamos.php"><i class="fa-solid fa-hand-holding-hand me-1"></i> Préstamos</a></li>
                    <li class="nav-item"><a class="nav-link" href="danados.php"><i class="fa-solid fa-triangle-exclamation me-1"></i> Reporte de Daños</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3 text-white">
                    <!-- BOTÓN DE MODO OSCURO -->
                    <button id="btnToggleDark" class="btn btn-outline-light btn-sm rounded-pill px-3" onclick="toggleModoOscuro()">
                        <i class="fa-solid fa-moon me-1" id="iconDark"></i> <span id="textToggleDark">Modo Oscuro</span>
                    </button>

                    <div class="text-end d-none d-sm-block ms-2">
                        <div class="fw-bold small"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></div>
                        <span class="badge bg-<?= ($_SESSION['rol'] ?? '') === 'admin' ? 'danger' : 'primary' ?> rounded-pill" style="font-size: 10px;">
                            <?= strtoupper($_SESSION['rol'] ?? 'Operador') ?>
                        </span>
                    </div>
                    <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <!-- HEADER PRINCIPAL -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h2 class="fw-extrabold text-dark mb-1">Panel de Control Operativo</h2>
                <p class="text-muted mb-0">Resumen ejecutivo del stock general, préstamos activos y alertas.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="alquileres.php" class="btn btn-primary btn-quick"><i class="fa-solid fa-plus"></i> Nuevo Alquiler</a>
                <a href="reservas.php" class="btn btn-warning text-dark btn-quick"><i class="fa-solid fa-calendar-plus"></i> Nueva Reserva</a>
            </div>
        </div>

        <!-- TARJETAS CON MANTENIMIENTO DE COLORES -->
        <div class="row g-4 mb-4">
            <!-- Mesas -->
            <div class="col-md-6 col-xl-3">
                <div class="stat-banner banner-blue shadow-sm">
                    <i class="fa-solid fa-table bg-icon"></i>
                    <span class="text-white-50 fw-semibold text-uppercase small">Mesas Disponibles</span>
                    <h2 class="fw-bold my-2 text-white"><?= number_format($mesas['cantidad_disponible']) ?> <span class="fs-6 fw-normal opacity-75">/ <?= number_format($mesas['cantidad_total']) ?></span></h2>
                    <div class="progress bg-white bg-opacity-25 mt-3" style="height: 6px;">
                        <div class="progress-bar bg-white" style="width: <?= $pct_mesas ?>%"></div>
                    </div>
                    <small class="mt-2 d-block text-white-50"><?= $pct_mesas ?>% de capacidad en almacén</small>
                </div>
            </div>

            <!-- Sillas -->
            <div class="col-md-6 col-xl-3">
                <div class="stat-banner banner-green shadow-sm">
                    <i class="fa-solid fa-chair bg-icon"></i>
                    <span class="text-white-50 fw-semibold text-uppercase small">Sillas Disponibles</span>
                    <h2 class="fw-bold my-2 text-white"><?= number_format($sillas['cantidad_disponible']) ?> <span class="fs-6 fw-normal opacity-75">/ <?= number_format($sillas['cantidad_total']) ?></span></h2>
                    <div class="progress bg-white bg-opacity-25 mt-3" style="height: 6px;">
                        <div class="progress-bar bg-white" style="width: <?= $pct_sillas ?>%"></div>
                    </div>
                    <small class="mt-2 d-block text-white-50"><?= $pct_sillas ?>% de capacidad en almacén</small>
                </div>
            </div>

            <!-- Préstamos Activos -->
            <div class="col-md-6 col-xl-3">
                <div class="stat-banner banner-orange shadow-sm">
                    <i class="fa-solid fa-hand-holding-hand bg-icon"></i>
                    <span class="text-white-50 fw-semibold text-uppercase small">Préstamos Activos</span>
                    <h2 class="fw-bold my-2 text-white"><?= number_format($pres_activos_cnt) ?> <span class="fs-6 fw-normal opacity-75">registros</span></h2>
                    <p class="mb-0 text-white-50 small mt-3"><i class="fa-solid fa-clock me-1"></i> Recursos fuera de almacén</p>
                </div>
            </div>

            <!-- Daños -->
            <div class="col-md-6 col-xl-3">
                <div class="stat-banner banner-red shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation bg-icon"></i>
                    <span class="text-white-50 fw-semibold text-uppercase small">Averías (2026)</span>
                    <h2 class="fw-bold my-2 text-white"><?= number_format($total_danados) ?> <span class="fs-6 fw-normal opacity-75">uds.</span></h2>
                    <p class="mb-0 text-white-50 small mt-3"><i class="fa-solid fa-wrench me-1"></i> Muebles reportados con daño</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- GRÁFICO ESTADÍSTICO CON COLORES -->
            <div class="col-lg-7">
                <div class="dash-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-column text-primary me-2"></i> Distribución del Inventario</h5>
                        <span class="badge bg-light text-dark border">En tiempo real</span>
                    </div>
                    <div style="position: relative; height:280px;">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- ACCIONES RÁPIDAS -->
            <div class="col-lg-5">
                <div class="dash-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-bolt text-warning me-2"></i> Accesos Directos</h5>
                        <div class="list-group list-group-flush gap-2">
                            <a href="alquileres.php" class="list-group-item list-group-item-action border rounded-3 p-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 me-3"><i class="fa-solid fa-file-invoice-dollar fs-5"></i></div>
                                    <div>
                                        <div class="fw-bold">Gestión de Alquileres_ mi comercial Comercial</div>
                                        <small class="text-muted">Paquetes, cobro libre y emisión de recibos</small>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-muted"></i>
                            </a>
                            <a href="reservas.php" class="list-group-item list-group-item-action border rounded-3 p-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 me-3"><i class="fa-solid fa-calendar-check fs-5"></i></div>
                                    <div>
                                        <div class="fw-bold">Gestión de Reservas</div>
                                        <small class="text-muted">Agendar mobiliario para eventos</small>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-muted"></i>
                            </a>
                            <a href="danados.php" class="list-group-item list-group-item-action border rounded-3 p-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="p-2 bg-danger bg-opacity-10 text-danger rounded-3 me-3"><i class="fa-solid fa-triangle-exclamation fs-5"></i></div>
                                    <div>
                                        <div class="fw-bold">Reporte de Daños por Mes</div>
                                        <small class="text-muted">Registro mensual Enero - Octubre</small>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA DE PRÉSTAMOS RECIENTES -->
        <div class="dash-card overflow-hidden">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Préstamos y Alquileres Recientes</h5>
                <a href="alquileres.php" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill">Ver todos los alquileres</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Solicitante</th>
                            <th>Modalidad / Paquete</th>
                            <th>Monto Total</th>
                            <th>Fecha Alquiler</th>
                            <th class="pe-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimos_prestamos)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No hay préstamos o alquileres activos registrados actualmente.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ultimos_prestamos as $p): 
                                $solic = $p['solicitante'] ?? $p['nombre_solicitante'] ?? 'N/A';
                                $paquete = $p['detalle_paquete'] ?? 'Unitario Libre';
                                $costo = $p['costo_total'] ?? 0;
                                $fecha = $p['fecha_prestamo'] ?? date('Y-m-d H:i:s');
                            ?>
                                <tr>
                                    <td class="ps-4 fw-bold">
                                        <i class="fa-solid fa-user me-2 text-primary"></i><?= htmlspecialchars((string)$solic) ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars((string)$paquete) ?></span></td>
                                    <td class="fw-bold text-success">$<?= number_format((float)$costo, 2) ?></td>
                                    <td class="text-muted"><small><?= date('d/m/Y h:i A', strtotime($fecha)) ?></small></td>
                                    <td class="pe-4">
                                        <span class="badge badge-soft-warning px-3 py-2 rounded-pill"><i class="fa-solid fa-spinner me-1"></i> <?= htmlspecialchars($p['estado'] ?? 'Activo') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- SCRIPT CHART.JS -->
    <script>
        let inventoryChart;
        const ctx = document.getElementById('inventoryChart').getContext('2d');

        function createChart(textColor = '#1e293b') {
            if (inventoryChart) inventoryChart.destroy();
            inventoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Mesas', 'Sillas'],
                    datasets: [
                        {
                            label: 'Disponibles',
                            data: [<?= $mesas['cantidad_disponible'] ?>, <?= $sillas['cantidad_disponible'] ?>],
                            backgroundColor: '#2563eb',
                            borderRadius: 6
                        },
                        {
                            label: 'Prestadas / Alquiladas',
                            data: [<?= $mesas['cantidad_prestada'] ?>, <?= $sillas['cantidad_prestada'] ?>],
                            backgroundColor: '#f59e0b',
                            borderRadius: 6
                        },
                        {
                            label: 'Reservadas',
                            data: [<?= $mesas['cantidad_reservada'] ?>, <?= $sillas['cantidad_reservada'] ?>],
                            backgroundColor: '#10b981',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: { color: textColor }
                        }
                    },
                    scales: {
                        x: { ticks: { color: textColor } },
                        y: { beginAtZero: true, ticks: { color: textColor } }
                    }
                }
            });
        }

        function toggleModoOscuro() {
            const body = document.body;
            const textBtn = document.getElementById('textToggleDark');
            const iconBtn = document.getElementById('iconDark');

            body.classList.toggle('dark-mode');

            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme_dark', 'active');
                textBtn.textContent = 'Modo Claro';
                iconBtn.className = 'fa-solid fa-sun me-1';
                createChart('#f8fafc');
            } else {
                localStorage.setItem('theme_dark', 'inactive');
                textBtn.textContent = 'Modo Oscuro';
                iconBtn.className = 'fa-solid fa-moon me-1';
                createChart('#1e293b');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme_dark');
            const textBtn = document.getElementById('textToggleDark');
            const iconBtn = document.getElementById('iconDark');

            if (savedTheme === 'active') {
                document.body.classList.add('dark-mode');
                if (textBtn) textBtn.textContent = 'Modo Claro';
                if (iconBtn) iconBtn.className = 'fa-solid fa-sun me-1';
                createChart('#f8fafc');
            } else {
                createChart('#1e293b');
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>