<?php require_once __DIR__ . '/../config/database.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Mobiliario - Mesas y Sillas</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome para iconos -->
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservas.php"><i class="fa-solid fa-calendar-check"></i> Reservas</a></li>
                    <li class="nav-item"><a class="nav-link" href="prestamos.php"><i class="fa-solid fa-handshake"></i> Préstamos</a></li>
                    <li class="nav-item"><a class="nav-link" href="devoluciones.php"><i class="fa-solid fa-rotate-left"></i> Devoluciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-warning" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-shield"></i> <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text text-muted small">Rol: <?= ucfirst($_SESSION['rol']) ?></span></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container py-4">