-- Création de la base de données si elle n'existe pas déjà
CREATE DATABASE IF NOT EXISTS helix_db;

-- Utilisation de la base de données
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
    user_id INT DEFAULT NULL,  -- Permettre NULL pour user_id
    action VARCHAR(255) NOT NULL,
    request_method VARCHAR(10) NOT NULL,
    request_url TEXT,
    request_data TEXT,
    response_code INT,
    response_body TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table des catégories d'aliments (food_categories)
CREATE TABLE food_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Table des aliments spécifiques (food_items)
CREATE TABLE food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,  -- Nom de l'aliment (ex: Courgette)
    unit VARCHAR(50) NOT NULL,  -- 'kg' pour poids ou 'unit' pour unité
    weight DECIMAL(10, 2) NOT NULL,  -- Poids en kg ou par unité
    price_per_unit DECIMAL(10, 2) NOT NULL,
    barcode VARCHAR(255) NOT NULL UNIQUE,  -- Code-barres unique pour chaque produit
    FOREIGN KEY (category_id) REFERENCES food_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table des entrepôts (warehouses)
CREATE TABLE warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(255) NOT NULL,
    rack_capacity INT NOT NULL,
    current_stock INT DEFAULT 0,
    address VARCHAR(255) DEFAULT NULL -- Adresse qui peut être NULL
) ENGINE=InnoDB;

-- Table des articles en stock (stock_items)
CREATE TABLE stock_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_item_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,  -- Quantité peut être en kg ou en unité
    FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table des mouvements de stock (stock_movements)
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_item_id INT NOT NULL,
    movement_type ENUM('IN', 'OUT') NOT NULL,  -- 'IN' pour entrée, 'OUT' pour sortie
    quantity DECIMAL(10, 2) NOT NULL,  -- Quantité ajoutée ou retirée
    movement_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table des points de collecte (collection_points)
CREATE TABLE collection_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Table des points de don (donation_points)
CREATE TABLE donation_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Insertion des données exemples dans les tables
INSERT INTO food_categories (name) VALUES
('Fruits'),
('Légumes'),
('Produits laitiers'),
('Boulangerie');

INSERT INTO food_items (category_id, name, unit, weight, price_per_unit, barcode) VALUES
((SELECT id FROM food_categories WHERE name = 'Fruits'), 'Pommes', 'kg', 1.0, 2.5, '1234567890123'),
((SELECT id FROM food_categories WHERE name = 'Fruits'), 'Bananes', 'kg', 1.0, 1.8, '1234567890124'),
((SELECT id FROM food_categories WHERE name = 'Légumes'), 'Courgettes', 'kg', 1.0, 2.0, '1234567890125'),
((SELECT id FROM food_categories WHERE name = 'Produits laitiers'), 'Lait', 'unit', 1.0, 0.9, '1234567890126'),
((SELECT id FROM food_categories WHERE name = 'Boulangerie'), 'Pain', 'unit', 0.5, 1.2, '1234567890127');

INSERT INTO warehouses (location, rack_capacity, current_stock, address) VALUES
('Warehouse A', 10000, 5000, '1 rue du Commerce, Paris'),
('Warehouse B', 15000, 8000, '15 avenue des Champs, Paris'),
('Warehouse C', 20000, 15000, NULL);

INSERT INTO stock_items (food_item_id, warehouse_id, quantity) VALUES
((SELECT id FROM food_items WHERE name = 'Pommes' LIMIT 1), (SELECT id FROM warehouses WHERE location = 'Warehouse A' LIMIT 1), 100),
((SELECT id FROM food_items WHERE name = 'Bananes' LIMIT 1), (SELECT id FROM warehouses WHERE location = 'Warehouse A' LIMIT 1), 200),
((SELECT id FROM food_items WHERE name = 'Courgettes' LIMIT 1), (SELECT id FROM warehouses WHERE location = 'Warehouse B' LIMIT 1), 150),
((SELECT id FROM food_items WHERE name = 'Lait' LIMIT 1), (SELECT id FROM warehouses WHERE location = 'Warehouse B' LIMIT 1), 500);

INSERT INTO collection_points (name, address) VALUES
('Point A', '2 rue Gervex, Paris'),
('Point B', '34 rue de Clichy, Paris'),
('Point C', '25 avenue Montaigne, Paris');

INSERT INTO donation_points (name, address) VALUES
('Point de Don A', '10 rue de la Paix, Paris'),
('Point de Don B', '20 avenue des Ternes, Paris'),
('Point de Don C', '30 boulevard Saint-Germain, Paris');
