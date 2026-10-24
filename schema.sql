SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS time_slots;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    location VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE time_slots (
    time_slot_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    slot_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_time_slots_activity
        FOREIGN KEY (activity_id) 
        REFERENCES activities (activity_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    time_slot_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservations_user
        FOREIGN KEY (user_id) 
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_reservations_slot
        FOREIGN KEY (time_slot_id) 
        REFERENCES time_slots (time_slot_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT uq_user_slot UNIQUE (user_id, time_slot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;