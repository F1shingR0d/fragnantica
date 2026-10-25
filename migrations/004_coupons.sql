-- Upgrade for discount coupons, WITHOUT deleting any data.
-- Run 001, 002 and 003 first. Safe to run more than once.
--
-- How to run: phpMyAdmin > select fragrance_store > Import > choose this file > Go
-- Written for MariaDB (the database that ships with XAMPP).

USE fragrance_store;

-- 1. coupons the admin creates and customers enter at checkout
--    discount_type 'percent': discount_value % off the items total; 'fixed': discount_value pesos off
--    min_order: the items total needed to use it; max_uses: NULL = no limit
--    starts_at / expires_at: NULL = no start / end date
CREATE TABLE IF NOT EXISTS coupon (
    coupon_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    discount_type ENUM('percent', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_uses INT NULL,
    starts_at DATE NULL,
    expires_at DATE NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2. orders: the coupon used and how much it took off (older orders: none, 0)
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS coupon_id INT NULL AFTER total_amount,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER coupon_id;

-- the foreign key, only if it is not there yet
SET @fk := (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'
              AND COLUMN_NAME = 'coupon_id' AND REFERENCED_TABLE_NAME = 'coupon');
SET @sql := IF(@fk = 0, 'ALTER TABLE orders ADD FOREIGN KEY (coupon_id) REFERENCES coupon (coupon_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. the views now show the items total, the discount and the grand total after the discount
CREATE OR REPLACE VIEW order_totals_view AS
SELECT order_id,
       COUNT(*) AS item_count,
       SUM(quantity) AS total_units,
       SUM(quantity * price) AS items_total
FROM order_items
GROUP BY order_id;

CREATE OR REPLACE VIEW order_summary_view AS
SELECT o.order_id, o.user_id, u.name AS customer_name, u.email AS customer_email,
       o.status, o.shipping_address, o.payment_method, o.payment_status, o.paid_at,
       o.created_at, o.updated_at,
       COALESCE(t.item_count, 0) AS item_count,
       COALESCE(t.total_units, 0) AS total_units,
       COALESCE(t.items_total, 0) AS items_total,
       c.code AS coupon_code,
       o.discount_amount,
       COALESCE(t.items_total, 0) - o.discount_amount AS grand_total
FROM orders o
INNER JOIN users u USING (user_id)
LEFT JOIN order_totals_view t USING (order_id)
LEFT JOIN coupon c ON c.coupon_id = o.coupon_id;

-- 4. a sample coupon: 10% off orders of 500 pesos or more
INSERT IGNORE INTO coupon (code, description, discount_type, discount_value, min_order, max_uses, status) VALUES
('WELCOME10', '10% off your order', 'percent', 10.00, 500.00, NULL, 'active');
