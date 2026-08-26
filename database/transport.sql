-- Schema for Local Transport Fair Guide
CREATE DATABASE IF NOT EXISTS local_transport_fair_guide CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE local_transport_fair_guide;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  category ENUM('fare','route','service','app','safety','other') NOT NULL DEFAULT 'other',
  severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  subject VARCHAR(200) NOT NULL,
  route_name VARCHAR(180) DEFAULT NULL,
  details TEXT NOT NULL,
  status ENUM('pending','reviewing','resolved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE locations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  region VARCHAR(120) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE routes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  start_location_id INT UNSIGNED NOT NULL,
  end_location_id INT UNSIGNED NOT NULL,
  fare DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  description TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  FOREIGN KEY (start_location_id) REFERENCES locations(id) ON DELETE RESTRICT,
  FOREIGN KEY (end_location_id) REFERENCES locations(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE favorites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  route_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fare_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  route_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  route_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comments TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (name, email, password, role, created_at) VALUES
('Administrator', 'admin@gmai.com', '$2y$10$HnhV69rlDkUAOw7BHUm.GeaCR2eRbmAgkRHQ9ocmNIydOLk02Aoam', 'admin', NOW());

INSERT INTO locations (name, region, created_at) VALUES
('Central Station', 'Downtown', NOW()),
('North Park', 'Northside', NOW()),
('Eastside Terminal', 'Eastside', NOW()),
('West End', 'Westside', NOW());

INSERT INTO routes (name, start_location_id, end_location_id, fare, description, created_at) VALUES
('Downtown Express', 1, 3, 2.50, 'Fast route connecting the city center with Eastside Terminal.', NOW()),
('Circle Line', 1, 4, 3.00, 'Scenic circular route around the city perimeter.', NOW()),
('North Connector', 2, 1, 2.20, 'Regular route between North Park and Central Station.', NOW());

INSERT INTO favorites (user_id, route_id, created_at) VALUES
(1, 1, NOW());

INSERT INTO fare_history (user_id, route_id, amount, created_at) VALUES
(1, 2, 3.00, NOW());

INSERT INTO reviews (user_id, route_id, rating, comments, created_at) VALUES
(1, 1, 5, 'Great service and reliable schedule.', NOW());


----------


CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_location VARCHAR(100) NOT NULL,
    end_location VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE route_fares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    fare DECIMAL(10,2) NOT NULL,
    min_time INT NOT NULL,  -- in minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);