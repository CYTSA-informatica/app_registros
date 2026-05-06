DROP DATABASE IF EXISTS registros_tareas;
CREATE DATABASE registros_tareas;
USE registros_tareas;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    contra_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    isAdmin BOOLEAN NOT NULL DEFAULT FALSE
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


-- TODO: Agregar una tabla con tasks predefinidas y una opcion para poner la suya propia

-- Usuario admin
INSERT INTO users (nombre, contra_hash, email, isAdmin) VALUES ('Admin', '$2b$12$Ct4qfYsSxAY8NMaU2G16kuRRs76P0WocwGSWE5cOznDFdZWgHud.2', 'informatica@cytsa.es', 1);


