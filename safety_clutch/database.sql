-- Safety Clutch System database (import via phpMyAdmin or run in the SQL tab)
CREATE DATABASE IF NOT EXISTS safety_clutch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE safety_clutch;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('passenger','driver') NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  vehicle_type ENUM('motorcycle','car','van') NULL,
  plate_no VARCHAR(20) NULL,
  expertise VARCHAR(150) NULL,
  is_online TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  passenger_id INT NOT NULL,
  driver_id INT NULL,
  pickup VARCHAR(200) NOT NULL,
  dropoff VARCHAR(200) NOT NULL,
  distance_km DECIMAL(6,2) NOT NULL,
  vehicle_type ENUM('motorcycle','car','van') NOT NULL,
  fare DECIMAL(10,2) NOT NULL,
  discount_pct INT NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  payment_method ENUM('cash','gcash') NOT NULL,
  gcash_ref VARCHAR(40) NULL,
  status ENUM('pending','accepted','ongoing','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (passenger_id) REFERENCES users(id),
  FOREIGN KEY (driver_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL UNIQUE,
  driver_id INT NOT NULL,
  passenger_id INT NOT NULL,
  stars TINYINT NOT NULL,
  comment VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id),
  FOREIGN KEY (driver_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  sender_id INT NOT NULL,
  body VARCHAR(500) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id),
  FOREIGN KEY (sender_id) REFERENCES users(id)
) ENGINE=InnoDB;
