-- Safe Docker import copy of the original Torres Vehicle Rental schema.
-- Database DROP/CREATE statements were removed; import into the existing torres_rental database.

-- Torres Vehicle Rental - normalized relational database
-- Import this file into phpMyAdmin/MySQL.
-- This rebuild intentionally uses one users table for both customers and admins.
USE torres_rental;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NULL UNIQUE,
    username VARCHAR(60) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(150) NOT NULL UNIQUE,
    seats TINYINT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    vehicle_class VARCHAR(60) NOT NULL,
    image_path VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE vehicle_colors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    color_name VARCHAR(60) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    in_stock TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vehicle_color (vehicle_id, color_name),
    UNIQUE KEY uq_vehicle_color_pair (vehicle_id, id),
    CONSTRAINT fk_vehicle_colors_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED NOT NULL,
    color_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,
    phone VARCHAR(30) NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_time TIME NOT NULL,
    return_date DATE NOT NULL,
    return_time TIME NOT NULL,
    payment_method VARCHAR(30) NULL DEFAULT NULL,
    status ENUM('confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_at DATETIME NULL,
    CONSTRAINT fk_reservations_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_reservations_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reservations_color FOREIGN KEY (vehicle_id, color_id) REFERENCES vehicle_colors(vehicle_id, id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reservations_location FOREIGN KEY (location_id) REFERENCES locations(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscribers_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO locations (slug, name) VALUES
('dumaguete','Dumaguete City'),
('cebu','Cebu City'),
('manila','Manila'),
('davao','Davao City');

INSERT INTO vehicles (name, slug, seats, price, vehicle_class, image_path) VALUES
('Toyota Vios','toyota-vios',5,1800,'car-sedan','assets/img/VIOS-cropped.png'),
('Toyota Innova','toyota-innova',7,2500,'car-mpv','assets/img/INNOVA-cropped.png'),
('Fortuner','fortuner',7,3500,'car-suv','assets/img/FORTUNER-cropped.png'),
('Hiace Commuter','hiace-commuter',15,3800,'car-van','assets/img/HIACE-cropped.png'),
('Honda Civic','honda-civic',5,2000,'car-hatchback','assets/img/CIVIC-BLACK-cropped.png'),
('Mitsubishi Xpander','mitsubishi-xpander',7,2300,'car-mpv','assets/img/XPANDER-WHITE-cropped.png'),
('Isuzu D-Max','isuzu-d-max',5,2800,'car-pickup','assets/img/DMAX-WHITE-cropped.png'),
('Yamaha Fazzio','yamaha-fazzio',2,800,'motorcycle-scooter','assets/img/YAMAHA-FAZZIO.png'),
('Honda Click 125','honda-click-125',2,700,'motorcycle-scooter','assets/img/HONDA-CLICK.png'),
('Honda BeAT','honda-beat',2,600,'motorcycle-scooter','assets/img/HONDA-BEAT.png');

INSERT INTO vehicle_colors (vehicle_id,color_name,image_path) VALUES
((SELECT id FROM vehicles WHERE slug='toyota-vios'),'Red','assets/img/VIOS-cropped.png'),
((SELECT id FROM vehicles WHERE slug='toyota-vios'),'Black','assets/img/VIOS-BLACK-cropped.png'),
((SELECT id FROM vehicles WHERE slug='toyota-vios'),'Blue','assets/img/VIOS-BLUE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='toyota-vios'),'Grey','assets/img/VIOS-GREY-cropped.png'),
((SELECT id FROM vehicles WHERE slug='toyota-vios'),'White','assets/img/VIOS-WHITE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='toyota-innova'),'Silver','assets/img/INNOVA-cropped.png'),
((SELECT id FROM vehicles WHERE slug='toyota-innova'),'Black','assets/img/INNOVA-BLACK-cropped.png'),
((SELECT id FROM vehicles WHERE slug='toyota-innova'),'Maroon','assets/img/INNOVA-MAROON-cropped.png'),
((SELECT id FROM vehicles WHERE slug='fortuner'),'Black','assets/img/FORTUNER-cropped.png'),
((SELECT id FROM vehicles WHERE slug='fortuner'),'Blue','assets/img/FORTUNER-BLUE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='hiace-commuter'),'White','assets/img/HIACE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='hiace-commuter'),'Red','assets/img/HIACE-RED-cropped.png'),
((SELECT id FROM vehicles WHERE slug='hiace-commuter'),'Off-White','assets/img/HIACE-OFFWHITE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='honda-civic'),'Black','assets/img/CIVIC-BLACK-cropped.png'),
((SELECT id FROM vehicles WHERE slug='honda-civic'),'Blue','assets/img/CIVIC-BLUE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='mitsubishi-xpander'),'White','assets/img/XPANDER-WHITE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='mitsubishi-xpander'),'Black','assets/img/XPANDER-BLACK-cropped.png'),
((SELECT id FROM vehicles WHERE slug='mitsubishi-xpander'),'Red','assets/img/XPANDER-RED-cropped.png'),
((SELECT id FROM vehicles WHERE slug='mitsubishi-xpander'),'Orange','assets/img/XPANDER-ORANGE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='isuzu-d-max'),'White','assets/img/DMAX-WHITE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='isuzu-d-max'),'Blue','assets/img/DMAX-BLUE-cropped.png'),
((SELECT id FROM vehicles WHERE slug='isuzu-d-max'),'Grey','assets/img/DMAX-GREY-cropped.png'),
((SELECT id FROM vehicles WHERE slug='isuzu-d-max'),'Red','assets/img/DMAX-RED-cropped.png'),
((SELECT id FROM vehicles WHERE slug='yamaha-fazzio'),'Green','assets/img/YAMAHA-FAZZIO.png'),
((SELECT id FROM vehicles WHERE slug='yamaha-fazzio'),'White','assets/img/YAMAHA-FAZZIO.png'),
((SELECT id FROM vehicles WHERE slug='yamaha-fazzio'),'Black','assets/img/YAMAHA-FAZZIO.png'),
((SELECT id FROM vehicles WHERE slug='yamaha-fazzio'),'Red','assets/img/YAMAHA-FAZZIO.png'),
((SELECT id FROM vehicles WHERE slug='honda-click-125'),'Red','assets/img/HONDA-CLICK.png'),
((SELECT id FROM vehicles WHERE slug='honda-click-125'),'Black','assets/img/HONDA-CLICK.png'),
((SELECT id FROM vehicles WHERE slug='honda-click-125'),'White','assets/img/HONDA-CLICK.png'),
((SELECT id FROM vehicles WHERE slug='honda-click-125'),'Blue','assets/img/HONDA-CLICK.png'),
((SELECT id FROM vehicles WHERE slug='honda-beat'),'White','assets/img/HONDA-BEAT.png'),
((SELECT id FROM vehicles WHERE slug='honda-beat'),'Black','assets/img/HONDA-BEAT.png'),
((SELECT id FROM vehicles WHERE slug='honda-beat'),'Red','assets/img/HONDA-BEAT.png'),
((SELECT id FROM vehicles WHERE slug='honda-beat'),'Blue','assets/img/HONDA-BEAT.png');
