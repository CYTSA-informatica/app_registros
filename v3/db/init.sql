DROP DATABASE IF EXISTS registros_tareas;
CREATE DATABASE registros_tareas;
USE registros_tareas;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    contra_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    isAdmin BOOLEAN NOT NULL DEFAULT FALSE,
    INDEX idx_users_email (email)
);

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    address VARCHAR(255),
    dni VARCHAR(20),
    pais VARCHAR(50),
    postal VARCHAR(10),
    poblacion VARCHAR(100),
    provincia VARCHAR(100)
);

CREATE TABLE providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    contacto VARCHAR(255),
    movil VARCHAR(20),
    correo VARCHAR(255),
    categoria VARCHAR(100)
);

CREATE TABLE registers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    duracion int(11) NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente', 'en_progreso', 'completada') DEFAULT 'pendiente',
    notas TEXT,
    id_empleado INT,
    id_cliente INT,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_empleado FOREIGN KEY (id_empleado) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cliente FOREIGN KEY (id_cliente) REFERENCES clients(id)
);

CREATE TABLE register_clients (
    register_id INT NOT NULL,
    client_id INT NOT NULL,
    PRIMARY KEY (register_id, client_id),
    CONSTRAINT fk_register_clients_register FOREIGN KEY (register_id) REFERENCES registers(id) ON DELETE CASCADE,
    CONSTRAINT fk_register_clients_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE register_providers (
    register_id INT NOT NULL,
    provider_id INT NOT NULL,
    PRIMARY KEY (register_id, provider_id),
    CONSTRAINT fk_register_providers_register FOREIGN KEY (register_id) REFERENCES registers(id) ON DELETE CASCADE,
    CONSTRAINT fk_register_providers_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
);

CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selector CHAR(24) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_remember_user (user_id),
    INDEX idx_remember_expires (expires_at)
);


-- TODO: Agregar una tabla con tasks predefinidas y una opcion para poner la suya propia

-- Usuario admin
