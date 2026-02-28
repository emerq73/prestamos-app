
CREATE DATABASE IF NOT EXISTS prestamos_db;
USE prestamos_db;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    rol ENUM('admin', 'socio', 'operador') DEFAULT 'operador',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@demo.com', '$2y$10$U7CEMmRnVVnVzth8kq4Kz.CNHhUz3hX9V2uVPk09imljN77YPsC7i', 'admin'); -- contraseña: admin123
