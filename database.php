<?php
session_start();

try {
    // Archivo de base de datos SQLite en la raíz del proyecto
    $db_file = __DIR__ . '/database.sqlite';
    
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Creación de todas las tablas necesarias
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario TEXT,
            email TEXT,
            nombre TEXT,
            password TEXT NOT NULL,
            rol TEXT DEFAULT 'admin'
        );

        CREATE TABLE IF NOT EXISTS inventario_recursos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo TEXT NOT NULL UNIQUE,
            cantidad_total INTEGER DEFAULT 0,
            cantidad_disponible INTEGER DEFAULT 0,
            cantidad_prestada INTEGER DEFAULT 0,
            cantidad_danada INTEGER DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS prestamos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            solicitante TEXT NOT NULL,
            email_cliente TEXT,
            cantidad INTEGER NOT NULL,
            observaciones TEXT,
            costo_total REAL DEFAULT 0,
            detalle_paquete TEXT,
            estado TEXT DEFAULT 'Activo',
            fecha_prestamo DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS reservas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            solicitante TEXT NOT NULL,
            email_cliente TEXT,
            mesas INTEGER DEFAULT 0,
            sillas INTEGER DEFAULT 0,
            fecha_evento DATE,
            observaciones TEXT,
            estado TEXT DEFAULT 'Pendiente',
            fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS danados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo TEXT NOT NULL,
            cantidad INTEGER NOT NULL,
            motivo TEXT,
            fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS configuracion_precios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo_item TEXT NOT NULL,
            concepto TEXT NOT NULL,
            mesas_incluidas INTEGER DEFAULT 0,
            sillas_incluidas INTEGER DEFAULT 0,
            precio REAL NOT NULL
        );
    ");

    // Compatibilidad para columnas adicionales
    try { $pdo->exec("ALTER TABLE usuarios ADD COLUMN email TEXT;"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE usuarios ADD COLUMN nombre TEXT;"); } catch (Exception $e) {}

    // Insertar datos iniciales si el inventario está vacío
    $stmt = $pdo->query("SELECT COUNT(*) FROM inventario_recursos");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO inventario_recursos (tipo, cantidad_total, cantidad_disponible, cantidad_prestada, cantidad_danada) VALUES
            ('mesa', 20000, 20000, 0, 0),
            ('silla', 30000, 30000, 0, 0);

            INSERT INTO usuarios (usuario, email, nombre, password, rol) VALUES
            ('admin', 'aquiror608@gmail.com', 'Administrador', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1e8L/0zI/kOq5F3K7.Wq6rL8PqW1K2a', 'admin');

            INSERT INTO configuracion_precios (tipo_item, concepto, mesas_incluidas, sillas_incluidas, precio) VALUES
            ('paquete', 'Paquete Básico (1 Mesa + 4 Sillas)', 1, 4, 5.00),
            ('paquete', 'Paquete Evento (5 Mesas + 20 Sillas)', 5, 20, 22.00),
            ('paquete', 'Paquete Fiesta (10 Mesas + 40 Sillas)', 10, 40, 40.00);
        ");
    }

} catch (PDOException $e) {
    die("Error de conexión a la base de datos SQLite: " . $e->getMessage());
}
?>