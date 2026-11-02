-- CREATE DATABASE: make and select the fragrance_store database
CREATE DATABASE IF NOT EXISTS fragrance_store;
USE fragrance_store;

-- SETTING: turn off foreign key checks so the old tables can be dropped
SET FOREIGN_KEY_CHECKS = 0;

-- DROP statements: delete the old views and tables before making them again
DROP VIEW IF EXISTS order_totals_view;
DROP VIEW IF EXISTS order_details_view;

DROP VIEW IF EXISTS order_summary_view;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS cart;

DROP TABLE IF EXISTS review;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS coupon;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS stock;
DROP TABLE IF EXISTS product;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS customer;
DROP TABLE IF EXISTS users;

-- CREATE TABLE: customers and admins (login, photo, role, status)
CREATE TABLE users (
    user_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    img_path VARCHAR(255) NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL
);

-- CREATE TABLE: delivery details, one row per user
CREATE TABLE customer (
    customer_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    addressline VARCHAR(255) NOT NULL,
    town VARCHAR(100) NOT NULL,
    zipcode VARCHAR(10) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
);

-- CREATE TABLE: product categories
CREATE TABLE category (
    category_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- CREATE TABLE: products with prices and the main photo
CREATE TABLE product (
    product_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    size VARCHAR(30) NOT NULL,
    description VARCHAR(255) NOT NULL,
    cost_price DECIMAL(10,2) NOT NULL,
    sell_price DECIMAL(10,2) NOT NULL,
    img_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (category_id) REFERENCES category (category_id)
);

-- CREATE TABLE: extra photos, many per product
CREATE TABLE product_images (
    image_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    product_id INT NOT NULL,
    img_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES product (product_id) ON DELETE CASCADE
);

-- CREATE TABLE: quantity in stock per product
CREATE TABLE stock (
    product_id INT PRIMARY KEY NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES product (product_id)
);

-- CREATE TABLE: orders with their status, payment method (cash or online + reference number) and date
CREATE TABLE orders (
    order_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_method ENUM('cash', 'online') NOT NULL DEFAULT 'cash',
    reference_no VARCHAR(50) NULL,
    date_placed DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (user_id)
);

-- CREATE TABLE: the products in each order with the price paid
CREATE TABLE order_items (
    order_item_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders (order_id),
    FOREIGN KEY (product_id) REFERENCES product (product_id)
);

-- CREATE TABLE: one review per customer per product
CREATE TABLE review (
    review_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL,
    comment VARCHAR(1000) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE (product_id, user_id),
    FOREIGN KEY (product_id) REFERENCES product (product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (user_id)
);

-- CREATE VIEW: one row per product in an order, with the customer and subtotal
CREATE VIEW order_details_view AS
SELECT o.order_id, o.user_id, u.name, u.email, c.addressline, c.town, c.zipcode, c.phone,
       o.status, o.payment_method, o.reference_no, o.date_placed, p.product_id, p.name AS product_name, p.brand, p.size,
       oi.quantity, oi.price, oi.quantity * oi.price AS subtotal
FROM orders o
INNER JOIN users u ON u.user_id = o.user_id
LEFT JOIN customer c ON c.user_id = o.user_id
INNER JOIN order_items oi ON oi.order_id = o.order_id
INNER JOIN product p ON p.product_id = oi.product_id;

-- CREATE VIEW: one row per order with its grand total
CREATE VIEW order_totals_view AS
SELECT o.order_id, o.user_id, u.name, u.email, o.status, o.payment_method, o.reference_no, o.date_placed,
       SUM(oi.quantity) AS items, SUM(oi.quantity * oi.price) AS total
FROM orders o
INNER JOIN users u ON u.user_id = o.user_id
INNER JOIN order_items oi ON oi.order_id = o.order_id
GROUP BY o.order_id, o.user_id, u.name, u.email, o.status, o.payment_method, o.reference_no, o.date_placed;

-- INSERT: the sample admin and customer accounts
INSERT INTO users (user_id, name, email, password, role, status, created_at) VALUES
(1, 'Store Admin', 'admin@veloura.test', '$2y$10$SjZh2EyG8FJAFWVTnCOFlucQPci7cYpqx.urgWtL.Xv2IaZwH7mi2', 'admin', 'active', NOW()),
(2, 'Juan Dela Cruz', 'customer@veloura.test', '$2y$10$/uZECPDeaLq1aZg/YIOSjOg72Ypl.BC2TRcPlPTaC3jpcaoBlYm/a', 'customer', 'active', NOW());

-- INSERT: delivery details of the sample customer
INSERT INTO customer (user_id, addressline, town, zipcode, phone) VALUES
(2, '123 Rizal St., Brgy. San Antonio', 'Pasig City', '1600', '09171234567');

-- INSERT: the product categories
INSERT INTO category (category_id, name) VALUES
(2, 'Perfume Oils'),
(3, 'Scented Candles'),
(4, 'Reed Diffusers'),
(5, 'Aroma Diffusers'),
(6, 'Room Sprays'),
(7, 'Wax Melts'),
(8, 'Car Fragrances'),
(9, 'Essential Oils');

-- INSERT: sample products (photos are in product/images)
INSERT INTO product (product_id, category_id, name, brand, size, description, cost_price, sell_price, img_path) VALUES
(8, 2, 'Oud Attar Roll-On Oil', 'Aroma House', '6 ml', 'Alcohol-free perfume oil in a roll-on bottle.', 120.00, 249.00, 'images/sample_attar_oil.jpg'),
(9, 3, 'Mahogany Teakwood Candle', 'White Barn', '200 g', 'Glass jar candle with warm woody notes.', 650.00, 1150.00, 'images/sample_scented_candle.jpg'),
(10, 4, 'Lavender Reed Diffuser', 'Aroma House', '100 ml', 'Lavender oil with reed sticks, lasts up to 2 months.', 280.00, 499.00, 'images/sample_reed_diffuser.jpg'),
(11, 5, 'Ultrasonic Aroma Diffuser', 'Aroma House', '1 pc', '100 ml water tank with color-changing light.', 550.00, 999.00, 'images/sample_aroma_diffuser.jpg'),
(12, 6, 'Freshmatic Room Spray', 'Air Wick', '250 ml', 'Automatic spray dispenser with refill can.', 420.00, 699.00, 'images/sample_room_spray.jpg'),
(13, 7, 'Vanilla & Spiced Apple Wax Melts', 'Aroma House', '80 g', 'Soy wax melts for use with a wax warmer.', 120.00, 199.00, 'images/sample_wax_melts.jpg'),
(14, 8, 'Strawberry Car Fragrance', 'Aroma House', '8 ml', 'Hanging glass bottle, lasts about 30 days.', 60.00, 129.00, 'images/sample_car_fragrance.jpg'),
(15, 9, 'Essential Oil Starter Set', 'Young Living', '11 pcs', 'Set of 11 x 5 ml oils including lavender and lemon.', 2200.00, 3499.00, 'images/sample_essential_oils.jpg'),
(16, 3, 'Vanilla Bean Tin Candle', 'Veloura', '150 g', 'Travel tin candle with sweet vanilla and tonka bean.', 220.00, 399.00, 'images/sample_vanilla_tin_candle.jpg'),
(17, 3, 'Pine Forest Glass Candle', 'Veloura', '250 g', 'Green glass candle with fresh pine and cedarwood.', 380.00, 649.00, 'images/sample_pine_glass_candle.jpg'),
(18, 3, 'White Linen Soy Candle', 'Veloura', '200 g', 'Clean cotton and white musk in a clear glass jar.', 340.00, 599.00, 'images/sample_white_linen_candle.jpg'),
(19, 3, 'Honey Beeswax Candle Set', 'Aroma House', '3 pcs', 'Three hand-poured beeswax candles with a light honey scent.', 300.00, 549.00, 'images/sample_beeswax_candles.jpg'),
(20, 7, 'Ceramic Wax Melt Warmer', 'Veloura', '1 pc', 'Glazed ceramic warmer for wax melts and oils, uses a tealight.', 260.00, 449.00, 'images/sample_ceramic_wax_warmer.jpg'),
(21, 7, 'Tealight Wax Warmer', 'Aroma House', '1 pc', 'Classic ceramic burner for wax melts, 4 tealights included.', 180.00, 329.00, 'images/sample_tealight_wax_warmer.jpg'),
(22, 5, 'Tealight Oil Burner', 'Aroma House', '1 pc', 'Stone oil burner: add water and a few drops of essential oil.', 200.00, 359.00, 'images/sample_tealight_oil_burner.jpg'),
(23, 5, 'Smart Scent Diffuser', 'Veloura', '1 pc', 'Waterless diffuser you can switch on and off from your phone.', 1400.00, 2299.00, 'images/sample_smart_diffuser.jpg'),
(24, 6, 'Automatic Spray Dispenser', 'Veloura', '250 ml', 'Sprays a fresh linen scent every 30 minutes, refill included.', 450.00, 749.00, 'images/sample_spray_dispenser.jpg'),
(25, 6, 'Room Mist Trio', 'Aroma House', '3 x 100 ml', 'Rose, lavender and citrus room mists in glass bottles.', 520.00, 899.00, 'images/sample_room_mist_trio.jpg'),
(26, 9, 'Rosemary Essential Oil', 'Veloura', '30 ml', 'Pure rosemary oil with a dropper, for diffusers and oil burners.', 160.00, 299.00, 'images/sample_rosemary_oil.jpg'),
(27, 2, 'Perfume Oil Sampler Set', 'Aroma House', '12 x 3 ml', 'Twelve alcohol-free perfume oils to find your favorite scent.', 480.00, 799.00, 'images/sample_perfume_oil_sampler.jpg'),
(28, 8, 'Hanging Car Diffuser Bottle', 'Veloura', '10 ml', 'Glass bottle with a wooden cap, hang it on the rear-view mirror.', 90.00, 179.00, 'images/sample_hanging_car_diffuser.jpg'),
(29, 8, 'Ocean Breeze Gel Freshener', 'Aroma House', '70 g', 'Long-lasting gel air freshener for the car or a small room.', 70.00, 139.00, 'images/sample_ocean_gel_freshener.jpg');

-- INSERT: starting stock of each product
INSERT INTO stock (product_id, quantity) VALUES
(8, 30), (9, 12), (10, 20), (11, 6), (12, 14), (13, 40), (14, 50), (15, 5),
(16, 25), (17, 15), (18, 20), (19, 10), (20, 8), (21, 12), (22, 10), (23, 4),
(24, 9), (25, 11), (26, 30), (27, 7), (28, 35), (29, 45);

-- DEMO DATA START
-- DEMO DATA: customers, orders and reviews for the presentation.
-- Every demo customer uses the password customer123. Marco is deactivated (cannot log in).
-- Users and orders are found by email and LAST_INSERT_ID(), so this block also works on a database that already has data.

-- INSERT: demo customers
INSERT INTO users (name, email, password, role, status, created_at) VALUES
('Azmith', 'azmith@veloura.test', '$2y$10$/uZECPDeaLq1aZg/YIOSjOg72Ypl.BC2TRcPlPTaC3jpcaoBlYm/a', 'customer', 'active', '2026-09-20 09:12:00'),
('Kirk', 'kirk@veloura.test', '$2y$10$/uZECPDeaLq1aZg/YIOSjOg72Ypl.BC2TRcPlPTaC3jpcaoBlYm/a', 'customer', 'active', '2026-09-21 14:30:00'),
('Jasmine', 'jasmine@veloura.test', '$2y$10$/uZECPDeaLq1aZg/YIOSjOg72Ypl.BC2TRcPlPTaC3jpcaoBlYm/a', 'customer', 'active', '2026-09-23 18:05:00'),
('Paolo', 'paolo@veloura.test', '$2y$10$/uZECPDeaLq1aZg/YIOSjOg72Ypl.BC2TRcPlPTaC3jpcaoBlYm/a', 'customer', 'active', '2026-09-25 11:48:00'),
('Marco', 'marco@veloura.test', '$2y$10$/uZECPDeaLq1aZg/YIOSjOg72Ypl.BC2TRcPlPTaC3jpcaoBlYm/a', 'customer', 'inactive', '2026-09-26 08:20:00');

-- VARIABLES: the new user ids
SET @azmith = (SELECT user_id FROM users WHERE email = 'azmith@veloura.test');
SET @kirk = (SELECT user_id FROM users WHERE email = 'kirk@veloura.test');
SET @jasmine = (SELECT user_id FROM users WHERE email = 'jasmine@veloura.test');
SET @paolo = (SELECT user_id FROM users WHERE email = 'paolo@veloura.test');
SET @marco = (SELECT user_id FROM users WHERE email = 'marco@veloura.test');

-- INSERT: their delivery details
INSERT INTO customer (user_id, addressline, town, zipcode, phone) VALUES
(@azmith, '45 Mabini St., Brgy. Poblacion', 'Makati City', '1210', '09171112233'),
(@kirk, '12 Katipunan Ave., Brgy. Loyola Heights', 'Quezon City', '1108', '09182223344'),
(@jasmine, '7 Acacia Lane, Brgy. Kapitolyo', 'Pasig City', '1603', '09193334455'),
(@paolo, '88 Shaw Blvd., Brgy. Wack-Wack', 'Mandaluyong City', '1550', '09204445566'),
(@marco, '3 Sampaguita St., Brgy. Alabang', 'Muntinlupa City', '1780', '09215556677');

-- INSERT: orders (each order, then its products at the current sell price)
-- Azmith: completed order (can review 9, 10 and 16; 16 is left without a review for the live demo)
INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (@azmith, 'completed', 'cash', NULL, '2026-10-02 10:15:00');
SET @order = LAST_INSERT_ID();
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 9;
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 2, sell_price FROM product WHERE product_id = 10;
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 16;

-- Azmith: shipped order
INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (@azmith, 'shipped', 'cash', NULL, '2026-10-27 16:40:00');
SET @order = LAST_INSERT_ID();
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 23;

-- Kirk: completed order
INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (@kirk, 'completed', 'online', 'GC1029384756', '2026-10-04 20:05:00');
SET @order = LAST_INSERT_ID();
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 9;
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 3, sell_price FROM product WHERE product_id = 14;
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 26;

-- Kirk: pending order (for the admin "update status + email" demo)
INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (@kirk, 'pending', 'online', 'GC5566778899', '2026-10-30 09:22:00');
SET @order = LAST_INSERT_ID();
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 11;
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 2, sell_price FROM product WHERE product_id = 22;

-- Jasmine: completed order
INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (@jasmine, 'completed', 'cash', NULL, '2026-10-06 13:10:00');
SET @order = LAST_INSERT_ID();
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 2, sell_price FROM product WHERE product_id = 18;
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 10;

-- Jasmine: processing order (not completed yet, so she cannot review product 25)
INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (@jasmine, 'processing', 'online', 'MAYA88213456', '2026-10-29 19:45:00');
SET @order = LAST_INSERT_ID();
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 25;

-- Paolo: completed order
INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (@paolo, 'completed', 'cash', NULL, '2026-10-08 11:30:00');
SET @order = LAST_INSERT_ID();
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 2, sell_price FROM product WHERE product_id = 13;
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 20;

-- Paolo: cancelled order
INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (@paolo, 'cancelled', 'cash', NULL, '2026-10-20 08:55:00');
SET @order = LAST_INSERT_ID();
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 12;

-- Marco: completed order (his account was deactivated later)
INSERT INTO orders (user_id, status, payment_method, reference_no, date_placed) VALUES (@marco, 'completed', 'cash', NULL, '2026-10-03 15:00:00');
SET @order = LAST_INSERT_ID();
INSERT INTO order_items (order_id, product_id, quantity, price) SELECT @order, product_id, 1, sell_price FROM product WHERE product_id = 9;

-- INSERT: reviews by the customers who bought the products.
-- The bad words were already replaced by **** with censor() (includes/functions.php), the same way review/store.php saves them.
INSERT INTO review (product_id, user_id, rating, comment, created_at, updated_at) VALUES
(9, @azmith, 5, 'Smells like a cozy cabin. The scent fills my whole room in 20 minutes and it burns evenly.', '2026-10-09 19:20:00', NULL),
(10, @azmith, 4, '**** ang bango! My room smells like a spa now. Wish the reeds were a bit longer.', '2026-10-09 19:26:00', NULL),
(9, @kirk, 4, 'This candle is the ****, honestly. Strong scent throw but the glass jar gets really hot.', '2026-10-11 21:02:00', NULL),
(14, @kirk, 2, '****, the scent was gone after one week. Smells nice but it does not last.', '2026-10-18 08:40:00', NULL),
(26, @kirk, 5, 'Bought a second bottle. Great in my oil burner, very fresh and herbal.', '2026-10-11 21:10:00', '2026-10-25 17:35:00'),
(18, @jasmine, 5, 'Clean and soft, like freshly washed sheets. My favorite candle so far.', '2026-10-12 10:15:00', NULL),
(10, @jasmine, 3, 'Ang **** ng packaging, one reed was broken pagdating. The oil itself smells great though.', '2026-10-12 10:22:00', NULL),
(13, @paolo, 5, '****, sobrang bango ng vanilla and apple combo. Perfect for the rainy season.', '2026-10-14 22:48:00', NULL),
(20, @paolo, 3, 'Works well but the tealight is hard to reach. Looks pretty on my desk.', '2026-10-14 22:55:00', NULL),
(9, @marco, 1, '**** overpriced for the size. The **** courier also delivered it late.', '2026-10-07 12:00:00', NULL);
-- DEMO DATA END

-- SETTING: turn foreign key checks back on
SET FOREIGN_KEY_CHECKS = 1;
