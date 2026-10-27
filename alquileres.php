<?php
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$error = '';
$ultimo_id_recibo = null;

// Precios configurados
$precio_mesa = 2.00;
$precio_silla = 1.00;

// Obtener paquetes configurados si existen
$paquetes = [];
try {
    $stmt_paq_list = $pdo->query("SELECT * FROM configuracion_precios WHERE tipo_item = 'paquete'");
    if ($stmt_paq_list) {
        $paquetes = $stmt_paq_list->fetchAll();
    }
} catch (Exception $e) {
    // Si la tabla no existe o hay error, se ignora para evitar romper la página
}

// Función limpia para enviar el correo mediante la API HTTP de Resend
function enviarFacturaCorreo($email_destino, $nombre_cliente, $num_factura, $nombre_paquete, $cant_mesas, $cant_sillas, $costo_total, $observaciones) {
    $fecha_actual = date('d/m/Y h:i A');
    $asunto = "Comprobante de Alquiler N° #" . str_pad($num_factura, 6, "0", STR_PAD_LEFT);

    // Plantilla HTML de la factura
    $mensaje_html = "
    <!DOCTYPE html>
    <html lang='es'>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; background-color: #f8fafc; padding: 20px; color: #0f172a;'>
        <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
            <div style='border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px;'>
                <h2 style='color: #2563eb; margin: 0;'>CONTROL MOBILIARIO</h2>
                <small style='color: #64748b;'>Comprobante Oficial N° #" . str_pad($num_factura, 6, "0", STR_PAD_LEFT) . " ({$fecha_actual})</small>
            </div>

            <p>Estimado/a <strong>{$nombre_cliente}</strong>,</p>
            <p>Se ha generado el recibo oficial correspondiente su alquiler de mobiliario:</p>

            <table style='width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px;'>
                <thead>
                    <tr style='background-color: #f1f5f9; text-align: left;'>
                        <th style='padding: 10px; border-bottom: 2px solid #cbd5e1;'>Concepto</th>
                        <th style='padding: 10px; border-bottom: 2px solid #cbd5e1;'>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0;'>Modalidad</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0;'><strong>{$nombre_paquete}</strong></td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0;'>Mesas Contratadas</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0;'>{$cant_mesas} (\$2.00 USD c/u)</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0;'>Sillas Contratadas</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0;'>{$cant_sillas} (\$1.00 USD c/u)</td>
                    </tr>
                    <tr style='font-size: 16px; font-weight: bold; color: #16a34a; background-color: #f0fdf4;'>
                        <td style='padding: 12px 10px;'>TOTAL NETO:</td>
                        <td style='padding: 12px 10px;'>\$" . number_format($costo_total, 2) . " USD</td>
                    </tr>
                </tbody>
            </table>

            <div style='margin-top: 20px; background: #f8fafc; padding: 12px; border-radius: 8px; font-size: 13px; color: #475569;'>
                <strong>Observaciones:</strong> " . ($observaciones ? htmlspecialchars($observaciones) : 'Sin detalles adicionales.') . "
            </div>
            
            <hr style='border: none; border-top: 1px solid #e2e8f0; margin-top: 30px;'>
            <p style='font-size: 11px; color: #94a3b8; text-align: center;'>Comprobante emitido automáticamente por Control Mobiliario.</p>
        </div>
    </body>
    </html>";

    $api_key = 're_NbuC9PG4_5KYAqgrJs12qdVvMDZkmdseq';

    $data = [
        'from'    => 'Control Mobiliario <onboarding@resend.dev>',
        'to'      => [$email_destino],
        'subject' => $asunto,
        'html'    => $mensaje_html
    ];

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("Error de Resend: " . $err);
        return false;
    }

    return true;
}

// Procesar alquiler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_alquiler') {
    $solicitante = trim($_POST['solicitante']);
    $email_cliente = trim($_POST['email_cliente']);
    $tipo_seleccion = $_POST['tipo_seleccion'];
    $observaciones = trim($_POST['observaciones']);
    
    $cantidad_mesas = 0;
    $cantidad_sillas = 0;
    $costo_total = 0;
    $nombre_paquete = 'Alquiler Unitario Libre';

    if ($tipo_seleccion === 'unitario') {
        $cantidad_mesas = intval($_POST['mesas_unitarias'] ?? 0);
        $cantidad_sillas = intval($_POST['sillas_unitarias'] ?? 0);
        $costo_total = ($cantidad_mesas * $precio_mesa) + ($cantidad_sillas * $precio_silla);
    } else {
        $id_paq = intval($tipo_seleccion);
        $stmt_paq = $pdo->prepare("SELECT * FROM configuracion_precios WHERE id = ? AND tipo_item = 'paquete'");
        $stmt_paq->execute([$id_paq]);
        $paq_info = $stmt_paq->fetch();
        
        if ($paq_info) {
            $cantidad_mesas = $paq_info['mesas_incluidas'];
            $cantidad_sillas = $paq_info['sillas_incluidas'];
            $costo_total = $paq_info['precio'];
            $nombre_paquete = $paq_info['concepto'];
        }
    }

    if ($solicitante === '' || ($cantidad_mesas <= 0 && $cantidad_sillas <= 0)) {
        $error = "Debe ingresar el nombre del cliente y seleccionar al menos una mesa o silla.";
    } else {
        try {
            $pdo->beginTransaction();

            $total_piezas = $cantidad_mesas + $cantidad_sillas;
            $stmt = $pdo->prepare("INSERT INTO prestamos (solicitante, email_cliente, cantidad, observaciones, costo_total, detalle_paquete, estado) VALUES (?, ?, ?, ?, ?, ?, 'Activo')");
            $stmt->execute([$solicitante, $email_cliente, $total_piezas, $observaciones, $costo_total, $nombre_paquete]);
            $ultimo_id_recibo = $pdo->lastInsertId();

            // Descontar inventario
            if ($cantidad_mesas > 0) {
                $stmt_mesa = $pdo->prepare("UPDATE inventario_recursos SET cantidad_disponible = cantidad_disponible - ?, cantidad_prestada = cantidad_prestada + ? WHERE tipo = 'Mesa'");
                $stmt_mesa->execute([$cantidad_mesas, $cantidad_mesas]);
            }
            if ($cantidad_sillas > 0) {
                $stmt_silla = $pdo->prepare("UPDATE inventario_recursos SET cantidad_disponible = cantidad_disponible - ?, cantidad_prestada = cantidad_prestada + ? WHERE tipo = 'Silla'");
                $stmt_silla->execute([$cantidad_sillas, $cantidad_sillas]);
            }

            $pdo->commit();

            // Enviar correo si se especificó email
            if (!empty($email_cliente)) {
                enviarFacturaCorreo($email_cliente, $solicitante, $ultimo_id_recibo, $nombre_paquete, $cantidad_mesas, $cantidad_sillas, $costo_total, $observaciones);
            }

            $mensaje = "¡Alquiler registrado exitosamente! Se ha despachado la factura por correo a <strong>" . htmlspecialchars($email_cliente) . "</strong>.";

        } catch (Exception $ex) {
            $pdo->rollBack();
            $error = "Error al procesar el alquiler: " . $ex->getMessage();
        }
    }
}

// Consultar lista
$alquileres = $pdo->query("SELECT * FROM prestamos ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Alquileres - Control Mobiliario</title>
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
                    <li class="nav-item"><a class="nav-link active fw-semibold" href="alquileres.php"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Alquileres</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservas.php">Reservas</a></li>
                    <li class="nav-item"><a class="nav-link" href="prestamos.php">Préstamos Gratuito</a></li>
                    <li class="nav-item"><a class="nav-link" href="danados.php">Reporte de Daños</a></li>
                </ul>
                <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Salir</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="mb-4">
            <h2 class="fw-bold text-dark"><i class="fa-solid fa-cash-register text-success me-2"></i> Gestión de Alquileres Comercial</h2>
            <p class="text-muted">Registro de contratos con generación y despacho de recibos digitales por correo electrónico.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card p-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-cart-plus text-primary me-2"></i> Nuevo Alquiler</h5>
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success border-0 shadow-sm">
                            <?= $mensaje ?>
                            <?php if ($ultimo_id_recibo): ?>
                                <hr>
                                <a href="recibo.php?id=<?= $ultimo_id_recibo ?>" target="_blank" class="btn btn-sm btn-success fw-semibold w-100"><i class="fa-solid fa-receipt me-1"></i> Abrir Recibo en PDF</a>
                                <script>
                                    window.open('recibo.php?id=<?= $ultimo_id_recibo ?>', '_blank');
                                </script>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger border-0 shadow-sm"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="accion" value="crear_alquiler">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre del Cliente</label>
                            <input type="text" name="solicitante" class="form-control" required placeholder="Ej. Juan Pérez">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Correo del Cliente</label>
                            <input type="email" name="email_cliente" class="form-control" required placeholder="cliente@correo.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Seleccionar Modalidad</label>
                            <select name="tipo_seleccion" id="tipo_seleccion" class="form-select" onchange="toggleSeleccion()" required>
                                <option value="unitario">-- Elección Libre (Unitario) --</option>
                                <?php foreach ($paquetes as $paq): ?>
                                    <option value="<?= $paq['id'] ?>">
                                        <?= htmlspecialchars($paq['concepto']) ?> ($<?= number_format($paq['precio'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="seccion_unitaria" class="border p-3 rounded-3 mb-3 bg-light">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Mesas ($2.00 USD c/u)</label>
                                <input type="number" name="mesas_unitarias" class="form-control form-control-sm" min="0" value="0">
                            </div>
                            <div>
                                <label class="form-label small fw-semibold">Sillas ($1.00 USD c/u)</label>
                                <input type="number" name="sillas_unitarias" class="form-control form-control-sm" min="0" value="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Detalles del evento..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-semibold py-2"><i class="fa-solid fa-paper-plane me-1"></i> Registrar y Enviar Factura</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card p-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-file-invoice me-2"></i> Historial de Alquileres Registrados</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente / Correo</th>
                                    <th>Modalidad</th>
                                    <th>Costo Total</th>
                                    <th>Estado</th>
                                    <th class="text-center">Recibo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($alquileres)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No hay registros de alquileres.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($alquileres as $a): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($a['solicitante']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($a['email_cliente'] ?? 'Sin correo') ?></small>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($a['detalle_paquete'] ?? 'Unitario Libre') ?></span></td>
                                            <td class="fw-bold text-success">$<?= number_format($a['costo_total'] ?? 0, 2) ?> USD</td>
                                            <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($a['estado']) ?></span></td>
                                            <td class="text-center">
                                                <a href="recibo.php?id=<?= $a['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="fa-solid fa-receipt me-1"></i> Ver Recibo</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleSeleccion() {
            const select = document.getElementById('tipo_seleccion');
            const seccionU = document.getElementById('seccion_unitaria');
            seccionU.style.display = (select.value === 'unitario') ? 'block' : 'none';
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>