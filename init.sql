CREATE DATABASE IF NOT EXISTS helix_db;

USE helix_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    roles VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Table des véhicules
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_plate VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    fret_capacity INT NOT NULL,
    human_capacity INT NOT NULL
) ENGINE=InnoDB;

-- Table des événements
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_start DATETIME NOT NULL,
    event_end DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
) ENGINE=InnoDB;

-- Table des relations entre événements et véhicules
CREATE TABLE event_vehicle (
    event_id INT,
    vehicle_id INT,
    PRIMARY KEY (event_id, vehicle_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table des participants aux événements
CREATE TABLE event_participants (
    user_id INT,
    event_id INT,
    PRIMARY KEY (user_id, event_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table des logs
CREATE TABLE log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    request_method VARCHAR(10) NOT NULL,
    request_url TEXT,
    request_data TEXT,
    response_code INT,
    response_body TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table des entrepôts (warehouses)
CREATE TABLE warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(255) NOT NULL,
    rack_capacity INT NOT NULL,
    current_stock INT DEFAULT 0
) ENGINE=InnoDB;

-- Table des types d'aliments (food types)
CREATE TABLE food_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    unit VARCHAR(50) NOT NULL,  -- 'kg' pour poids ou 'unit' pour unité
    price_per_unit DECIMAL(10, 2) NOT NULL
) ENGINE=InnoDB;

-- Table des aliments (food items)
CREATE TABLE food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_type_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,  -- Quantité peut être en kg ou en unité
    FOREIGN KEY (food_type_id) REFERENCES food_types(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table des mouvements de stock (stock movements)
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_item_id INT NOT NULL,
    movement_type ENUM('IN', 'OUT') NOT NULL,  -- 'IN' pour entrée, 'OUT' pour sortie
    quantity DECIMAL(10, 2) NOT NULL,  -- Quantité ajoutée ou retirée
    movement_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insérer des exemples de types d'aliments
INSERT INTO food_types (name, unit, price_per_unit) VALUES
('Apples', 'kg', 2.5),
('Bananas', 'kg', 1.8),
('Milk', 'unit', 0.9),
('Bread', 'unit', 1.2);

-- Insérer des exemples d'entrepôts
INSERT INTO warehouses (location, rack_capacity, current_stock) VALUES
('Warehouse A', 10000, 5000),
('Warehouse B', 15000, 8000);
