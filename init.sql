CREATE DATABASE IF NOT EXISTS helix_db;

USE helix_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    roles VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(255) NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    rack_capacity INT NOT NULL,
    current_stock INT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS food_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    weight DECIMAL(10, 2) NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    barcode VARCHAR(255) NOT NULL UNIQUE,
    FOREIGN KEY (category_id) REFERENCES food_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_plate VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    fret_capacity INT NOT NULL,
    human_capacity INT NOT NULL,
    current_warehouse_id INT,
    FOREIGN KEY (current_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_item_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_item_id INT NOT NULL,
    movement_type ENUM('IN', 'OUT') NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    movement_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE collection_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS donation_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_start DATETIME NOT NULL,
    event_end DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_vehicle (
    event_id INT,
    vehicle_id INT,
    PRIMARY KEY (event_id, vehicle_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_participants (
    user_id INT,
    event_id INT,
    PRIMARY KEY (user_id, event_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    inviter_username VARCHAR(255) NOT NULL,
    inviter_email VARCHAR(255) NOT NULL,
    invitee_username VARCHAR(255) NOT NULL,
    invitee_email VARCHAR(255) NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    request_method VARCHAR(10) NOT NULL,
    request_url TEXT,
    request_data TEXT,
    response_code INT,
    response_body TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO food_categories (name) VALUES
('Fruits'),
('Légumes'),
('Produits laitiers'),
('Autres'),
('Boulangerie');

INSERT INTO food_items (category_id, name, unit, weight, price_per_unit, barcode) VALUES
((SELECT id FROM food_categories WHERE name = 'Fruits'), 'Pommes', 'kg', 1.0, 2.5, '1234567890123'),
((SELECT id FROM food_categories WHERE name = 'Fruits'), 'Bananes', 'kg', 1.0, 1.8, '1234567890124'),
((SELECT id FROM food_categories WHERE name = 'Légumes'), 'Courgettes', 'kg', 1.0, 2.0, '1234567890125'),
((SELECT id FROM food_categories WHERE name = 'Produits laitiers'), 'Lait', 'unit', 1.0, 0.9, '1234567890126'),
((SELECT id FROM food_categories WHERE name = 'Boulangerie'), 'Pain', 'unit', 0.5, 1.2, '1234567890127');

INSERT INTO warehouses (location, address, rack_capacity, current_stock) VALUES
('Warehouse A', '1 rue du Commerce, Paris', 10000, 5000),
('Warehouse B', '15 rue de la Paix, Paris', 15000, 8000),
('Warehouse C', '30 boulevard Saint-Germain, Paris', 20000, 12000);

INSERT INTO collection_points (name, address) VALUES
('Point A', '2 rue Gervex, Paris'),
('Point B', '34 rue de Clichy, Paris'),
('Point C', '25 avenue Montaigne, Paris');

INSERT INTO stock_items (food_item_id, warehouse_id, quantity) VALUES
((SELECT id FROM food_items WHERE name = 'Pommes' LIMIT 1), (SELECT id FROM warehouses WHERE location = 'Warehouse A' LIMIT 1), 100),
((SELECT id FROM food_items WHERE name = 'Bananes' LIMIT 1), (SELECT id FROM warehouses WHERE location = 'Warehouse A' LIMIT 1), 200),
((SELECT id FROM food_items WHERE name = 'Courgettes' LIMIT 1), (SELECT id FROM warehouses WHERE location = 'Warehouse B' LIMIT 1), 150),
((SELECT id FROM food_items WHERE name = 'Lait' LIMIT 1), (SELECT id FROM warehouses WHERE location = 'Warehouse B' LIMIT 1), 500);

INSERT INTO vehicles (id_plate, model, fret_capacity, human_capacity, current_warehouse_id) VALUES
('ABC-123', 'Camion A', 5000, 2, (SELECT id FROM warehouses WHERE location = 'Warehouse A' LIMIT 1)),
('DEF-456', 'Camion B', 7000, 2, (SELECT id FROM warehouses WHERE location = 'Warehouse B' LIMIT 1)),
('GHI-789', 'Camion C', 10000, 3, (SELECT id FROM warehouses WHERE location = 'Warehouse C' LIMIT 1));

INSERT INTO donation_points (name, address) VALUES
('Point de Don A', '10 rue de la Paix, Paris'),
('Point de Don B', '20 avenue des Ternes, Paris'),
('Point de Don C', '30 boulevard Saint-Germain, Paris');
