<?php
require_once 'database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Acceso no autorizado o ID de alquiler no especificado.");
}

$id_alquiler = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM prestamos WHERE id = ?");
$stmt->execute([$id_alquiler]);
$alquiler = $stmt->fetch();

if (!$alquiler) {
    die("El comprobante solicitado no existe.");
}

$num_factura = str_pad($alquiler['id'], 6, '0', STR_PAD_LEFT);
$fecha_emision = date('d/m/Y h:i A', strtotime($alquiler['fecha_prestamo']));

// Estimación de cantidades según modalidad y costo
$total_piezas = intval($alquiler['cantidad'] ?? 0);
$paquete_nombre = htmlspecialchars($alquiler['detalle_paquete'] ?? 'Alquiler Unitario Libre');
$costo_total = floatval($alquiler['costo_total'] ?? 0);
$subtotal = $costo_total;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Alquiler #<?= $num_factura ?> - Control Mobiliario</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { 
            background-color: #f1f5f9; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #0f172a; 
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .invoice-wrapper {
            max-width: 850px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            position: relative;
        }

        .invoice-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 40px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            background: rgba(37, 99, 235, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #38bdf8;
        }

        .invoice-badge {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-size: 12px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card {
            background: #f8fafc;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }

        .table-custom {
            margin-top: 20px;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table-custom thead th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-custom tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .total-box {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border-radius: 14px;
            padding: 24px;
        }

        .watermark-status {
            position: absolute;
            top: 220px;
            right: 50px;
            font-size: 75px;
            font-weight: 900;
            color: rgba(16, 185, 129, 0.06);
            transform: rotate(-15deg);
            pointer-events: none;
            user-select: none;
        }

        .signature-line {
            border-top: 1px solid #cbd5e1;
            width: 200px;
            margin: 0 auto;
        }

        @media print {
            body { background: #ffffff !important; }
            .invoice-wrapper { box-shadow: none !important; margin: 0 !important; width: 100% !important; border-radius: 0 !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container py-3">
    <!-- BOTONES DE ACCIÓN FLOTANTES -->
    <div class="d-flex justify-content-end gap-2 mb-3 max-w-850 mx-auto no-print" style="max-width: 850px;">
        <a href="alquileres.php" class="btn btn-light fw-semibold text-secondary rounded-pill px-4">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver a Alquileres
        </a>
        <button onclick="window.print()" class="btn btn-primary fw-semibold rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Imprimir / Guardar PDF
        </button>
    </div>

    <!-- DOCUMENTO RECIBO / FACTURA -->
    <div class="invoice-wrapper">
        <div class="watermark-status">PROCESADO</div>

        <!-- CABECERA PRINCIPAL -->
        <div class="invoice-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="brand-logo">
                        <i class="fa-solid fa-chair"></i>
                    </div>
                    <div>
                        <h4 class="fw-extrabold mb-0 tracking-wide">CONTROL MOBILIARIO</h4>
                        <small class="text-white-50">Sistema de Gestión & Alquiler de Eventos</small>
                    </div>
                </div>
                <div class="text-end">
                    <span class="invoice-badge"><i class="fa-solid fa-circle-check me-1"></i> Pago Confirmado</span>
                    <h5 class="fw-bold mt-2 mb-0 text-white">RECIBO N° #<?= $num_factura ?></h5>
                    <small class="text-white-50">Emisión: <?= $fecha_emision ?></small>
                </div>
            </div>
        </div>

        <!-- DATOS DEL CLIENTE Y SERVICIO -->
        <div class="p-4 p-md-5">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="info-card h-100">
                        <span class="text-muted text-uppercase fw-bold small d-block mb-2"><i class="fa-solid fa-user-check text-primary me-1"></i> Datos del Cliente / Solicitante</span>
                        <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($alquiler['solicitante']) ?></h5>
                        <?php if (!empty($alquiler['email_cliente'])): ?>
                            <div class="text-secondary small mb-1"><i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($alquiler['email_cliente']) ?></div>
                        <?php endif; ?>
                        <div class="text-muted small"><i class="fa-solid fa-id-card me-1"></i> Registro Interno #<?= $alquiler['id'] ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-card h-100">
                        <span class="text-muted text-uppercase fw-bold small d-block mb-2"><i class="fa-solid fa-receipt text-primary me-1"></i> Resumen de Contratación</span>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Modalidad:</span>
                            <span class="fw-bold text-dark small"><?= $paquete_nombre ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Total Piezas:</span>
                            <span class="fw-bold text-dark small"><?= $total_piezas ?> Unidades</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Estado de Entrega:</span>
                            <span class="badge bg-warning text-dark font-normal">Mobiliario Despachado</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA DESGLOSADA DE PRODUCTOS Y TARIFAS -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i> Detalle del Servicio y Tarifas Aplicadas</h6>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Descripción del Mobiliario</th>
                                <th class="text-center">Tipo Tarifario</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end">Importe Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= $paquete_nombre ?></div>
                                    <small class="text-muted">Alquiler de mesas y sillas para eventos con tarifa regulada ($2.00/Mesa, $1.00/Silla) o tarifa preferencial en paquete.</small>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-light text-dark border"><?= $alquiler['detalle_paquete'] ? 'Promocional' : 'Standard' ?></span>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill">Completado</span>
                                </td>
                                <td class="text-end align-middle fw-bold text-dark fs-6">$<?= number_format($subtotal, 2) ?> USD</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- LIQUIDACIÓN Y TOTALES -->
            <div class="row g-4 align-items-center mb-5">
                <div class="col-md-7">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="fw-bold text-dark d-block mb-1"><i class="fa-solid fa-comment-dots text-primary me-1"></i> Observaciones o Notas Adicionales:</small>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($alquiler['observaciones'] ?: 'Servicio registrado correctamente sin requerimientos especiales.') ?></p>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="total-box shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-white-50 small">Subtotal:</span>
                            <span class="fw-semibold">$<?= number_format($subtotal, 2) ?> USD</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-white-50 small">Impuestos / Cargos:</span>
                            <span class="fw-semibold">$0.00 USD</span>
                        </div>
                        <hr class="my-2 border-white opacity-25">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">TOTAL NETO:</span>
                            <h3 class="fw-extrabold mb-0 text-white">$<?= number_format($costo_total, 2) ?> USD</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TÉRMINOS Y FIRMAS DE CONFORMIDAD -->
            <div class="border-top pt-4">
                <div class="row g-4 text-center mt-3">
                    <div class="col-6">
                        <div class="signature-line mb-2"></div>
                        <small class="fw-bold text-dark d-block">Entregado por (Empresa)</small>
                        <small class="text-muted" style="font-size: 11px;">Control Mobiliario - Firma Autorizada</small>
                    </div>
                    <div class="col-6">
                        <div class="signature-line mb-2"></div>
                        <small class="fw-bold text-dark d-block">Recibido por (Cliente)</small>
                        <small class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($alquiler['solicitante']) ?></small>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <small class="text-muted d-block" style="font-size: 11px;">
                        Este documento sirve como comprobante oficial de pago y recepción de mobiliario. Todo equipo debe ser devuelto en la fecha pactada en óptimas condiciones.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>