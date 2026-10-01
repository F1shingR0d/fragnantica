-- Online Perfume Store System
-- Database: users, customer, perfume, stock
--
-- How to import: open phpMyAdmin > Import > choose this file > Go
-- WARNING: importing this file again deletes all data in these tables.
--
-- Default admin account (created at the bottom of this file):
--   email:    admin@perfumestore.com
--   password: admin123

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS perfume_store;
USE perfume_store;

DROP TABLE IF EXISTS stock;
DROP TABLE IF EXISTS perfume;
DROP TABLE IF EXISTS customer;
DROP TABLE IF EXISTS users;

-- customers and admins
CREATE TABLE users (
    user_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at DATETIME NOT NULL
);

-- customer delivery details (one row per user, filled in on the profile page)
CREATE TABLE customer (
    customer_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    addressline VARCHAR(255) NOT NULL,
    town VARCHAR(100) NOT NULL,
    zipcode VARCHAR(10) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (user_id)
);

-- perfume products
CREATE TABLE perfume (
    perfume_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    scent_type VARCHAR(50) NOT NULL,
    size_ml INT NOT NULL,
    cost_price DECIMAL(10,2) NOT NULL,
    sell_price DECIMAL(10,2) NOT NULL,
    img_path VARCHAR(255) NOT NULL
);

-- how many pieces of each perfume are available
CREATE TABLE stock (
    perfume_id INT PRIMARY KEY NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    FOREIGN KEY (perfume_id) REFERENCES perfume (perfume_id)
);

-- default admin account (password is admin123, hashed with password_hash)
INSERT INTO users (name, email, password, role, created_at)
VALUES ('Administrator', 'admin@perfumestore.com', '$2y$10$SIs15BU7A1.aVRjndlosaOEif6SkHizhqse5v90PqKeYel2Y/W8YC', 'admin', now());

-- sample perfumes (photos are in perfume/images, see perfume/images/CREDITS.txt)
INSERT INTO perfume (perfume_id, name, brand, scent_type, size_ml, cost_price, sell_price, img_path) VALUES
(1, 'No. 5 Eau de Parfum', 'Chanel', 'Floral', 100, 7200.00, 9950.00, 'images/sample_chanel_no5.jpg'),
(2, 'Sauvage', 'Dior', 'Fresh', 100, 5300.00, 7450.00, 'images/sample_dior_sauvage.jpg'),
(3, 'Acqua di Giò', 'Giorgio Armani', 'Aquatic', 100, 4400.00, 6300.00, 'images/sample_acqua_di_gio.jpg'),
(4, '1 Million', 'Paco Rabanne', 'Spicy', 100, 3900.00, 5600.00, 'images/sample_1_million.jpg'),
(5, 'Eros', 'Versace', 'Fresh', 100, 3400.00, 4950.00, 'images/sample_versace_eros.jpg'),
(6, 'Coco Mademoiselle', 'Chanel', 'Floral', 100, 6900.00, 9450.00, 'images/sample_coco_mademoiselle.jpg'),
(7, 'Le Male', 'Jean Paul Gaultier', 'Oriental', 125, 3600.00, 5200.00, 'images/sample_le_male.jpg');

INSERT INTO stock (perfume_id, quantity) VALUES
(1, 10),
(2, 15),
(3, 12),
(4, 20),
(5, 18),
(6, 8),
(7, 3);
