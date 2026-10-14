-- Base de datos para la prueba técnica de gestión de reservas (MisterPlan)
-- Estudiante / Candidato: Jorge Castillo

CREATE DATABASE IF NOT EXISTS reservas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservas_db;

-- Borramos las tablas si ya existen para poder reimportar el archivo limpiamente
DROP TABLE IF EXISTS reserva_evento;
DROP TABLE IF EXISTS reserva;

-- 1. Tabla principal de reservas
CREATE TABLE reserva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_huesped VARCHAR(150) NOT NULL,
    email_huesped VARCHAR(150) NOT NULL,
    nombre_alojamiento VARCHAR(150) NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada') DEFAULT 'pendiente' NOT NULL,
    importe DECIMAL(10, 2) NOT NULL,
    notas TEXT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    -- Índices para agilizar las búsquedas y los filtros requeridos
    INDEX idx_estado (estado),
    INDEX idx_fechas (fecha_entrada, fecha_salida),
    INDEX idx_nombre_huesped (nombre_huesped)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Historial de eventos de cada reserva (auditoría / timeline)
CREATE TABLE reserva_evento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL, -- creacion, confirmacion, cancelacion
    descripcion TEXT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    -- Relación con la tabla reserva (si se borra la reserva, se borra su historial)
    CONSTRAINT fk_reserva_evento_reserva 
        FOREIGN KEY (reserva_id) REFERENCES reserva(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- Datos de ejemplo para probar la aplicación y los filtros
-- =======================================================

-- Insertar reservas en diferentes estados
INSERT INTO reserva (id, nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion) VALUES
(1, 'Laura Martínez Gómez', 'laura.martinez@email.com', 'Hotel Rural El Mirador', '2026-11-01', '2026-11-05', 'confirmada', 380.00, 'Solicita cuna para bebé en la habitación principal.', '2026-10-01 10:30:00'),
(2, 'Carlos Fernández Ruiz', 'carlos.fdez@email.com', 'Apartamento Playa Dorada', '2026-11-10', '2026-11-17', 'pendiente', 720.50, 'Llegada prevista sobre las 21:00h (late check-in).', '2026-10-05 14:15:00'),
(3, 'Ana Belén Morales', 'anabelen.morales@email.com', 'Villa Las Palmeras Deluxe', '2026-10-20', '2026-10-25', 'cancelada', 1250.00, 'Cancelación por motivos médicos justificados.', '2026-09-28 09:00:00'),
(4, 'David Sánchez Ortega', 'david.sanchez@email.com', 'Cabaña Bosque Nublado', '2026-12-05', '2026-12-08', 'confirmada', 290.00, 'Viaja con mascota pequeña.', '2026-10-08 18:45:00'),
(5, 'Elena Castro Prieto', 'elena.castro@email.com', 'Suite Romántica El Molino', '2026-11-14', '2026-11-16', 'pendiente', 240.00, 'Celebración de aniversario.', '2026-10-10 11:20:00'),
(6, 'Javier López Navarro', 'javier.lopez@email.com', 'Hostal Centro Histórico', '2026-10-28', '2026-10-30', 'cancelada', 110.00, 'Cancelada por falta de confirmación.', '2026-10-02 16:00:00'),
(7, 'Marta Domínguez Gil', 'marta.dominguez@email.com', 'Casa Rural Sierra Nevada', '2026-12-24', '2026-12-30', 'confirmada', 1450.00, 'Reserva navideña para grupo familiar.', '2026-10-11 12:00:00'),
(8, 'Santiago Romero Vega', 'santiago.romero@email.com', 'Apartamento Sol y Mar', '2026-11-03', '2026-11-07', 'pendiente', 410.00, 'Requiere plaza de garaje.', '2026-10-13 08:30:00');

-- Historial de eventos correspondiente a las reservas anteriores
INSERT INTO reserva_evento (reserva_id, tipo, descripcion, fecha_creacion) VALUES
-- Reserva 1 (Confirmada)
(1, 'creacion', 'Reserva creada con estado pendiente.', '2026-10-01 10:30:00'),
(1, 'confirmacion', 'Reserva confirmada tras recibir el pago.', '2026-10-02 11:00:00'),

-- Reserva 2 (Pendiente)
(2, 'creacion', 'Reserva creada con estado pendiente.', '2026-10-05 14:15:00'),

-- Reserva 3 (Cancelada)
(3, 'creacion', 'Reserva creada con estado pendiente.', '2026-09-28 09:00:00'),
(3, 'confirmacion', 'Reserva confirmada por el cliente.', '2026-09-29 10:15:00'),
(3, 'cancelacion', 'Reserva cancelada a petición del huésped.', '2026-10-04 17:30:00'),

-- Reserva 4 (Confirmada)
(4, 'creacion', 'Reserva creada con estado pendiente.', '2026-10-08 18:45:00'),
(4, 'confirmacion', 'Reserva confirmada.', '2026-10-09 09:20:00'),

-- Reserva 5 (Pendiente)
(5, 'creacion', 'Reserva creada con estado pendiente.', '2026-10-10 11:20:00'),

-- Reserva 6 (Cancelada)
(6, 'creacion', 'Reserva creada con estado pendiente.', '2026-10-02 16:00:00'),
(6, 'cancelacion', 'Reserva cancelada por expiración del plazo.', '2026-10-04 12:00:00'),

-- Reserva 7 (Confirmada)
(7, 'creacion', 'Reserva creada con estado pendiente.', '2026-10-11 12:00:00'),
(7, 'confirmacion', 'Reserva confirmada con anticipo pagado.', '2026-10-12 10:00:00'),

-- Reserva 8 (Pendiente)
(8, 'creacion', 'Reserva creada con estado pendiente.', '2026-10-13 08:30:00');
