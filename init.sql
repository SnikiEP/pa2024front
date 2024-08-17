CREATE DATABASE IF NOT EXISTS helix_db;

USE helix_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    roles VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_plate VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    fret_capacity INT NOT NULL,
    human_capacity INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_start DATETIME NOT NULL,
    event_end DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE event_vehicle (
    event_id INT,
    vehicle_id INT,
    PRIMARY KEY (event_id, vehicle_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE event_participants (
    user_id INT,
    event_id INT,
    PRIMARY KEY (user_id, event_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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

INSERT INTO vehicles (id_plate, model, fret_capacity, human_capacity) VALUES
('ABC123', 'Van Model X', 1000, 3),
('XYZ789', 'Truck Model Y', 5000, 2);
