-- ============================================================
-- Violin Rental Agency — Full Schema
-- CPSC 3660 Group Project
-- ============================================================

CREATE DATABASE IF NOT EXISTS twog3669;
USE twog3669;

DROP TABLE IF EXISTS RENTAL_ITEM;
DROP TABLE IF EXISTS RENTAL;
DROP TABLE IF EXISTS PAYMENT;
DROP TABLE IF EXISTS RECEIPT;
DROP TABLE IF EXISTS MAINTENANCE_LOG;
DROP TABLE IF EXISTS PRODUCT;
DROP TABLE IF EXISTS MANUFACTURER_DISTRIBUTOR;
DROP TABLE IF EXISTS USERS;
DROP TABLE IF EXISTS CUSTOMER;

-- ============================================================
-- MANUFACTURER_DISTRIBUTOR (Strong entity)
-- ============================================================
CREATE TABLE MANUFACTURER_DISTRIBUTOR (
    manufacturer_id INT AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    country         VARCHAR(80),
    contact_email   VARCHAR(100),
    contact_phone   VARCHAR(30),
    CONSTRAINT pk_manufacturer PRIMARY KEY (manufacturer_id)
);

-- ============================================================
-- PRODUCT (Strong entity)
-- ============================================================
CREATE TABLE PRODUCT (
    product_id      INT AUTO_INCREMENT,
    type            VARCHAR(80)   NOT NULL,
    size            VARCHAR(20),
    price           DECIMAL(10,2) NOT NULL CHECK (price >= 0),
    stock           INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    manufacturer_id INT,
    CONSTRAINT pk_product    PRIMARY KEY (product_id),
    CONSTRAINT fk_product_mf FOREIGN KEY (manufacturer_id)
        REFERENCES MANUFACTURER_DISTRIBUTOR(manufacturer_id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

-- ============================================================
-- MAINTENANCE_LOG (Weak entity — depends on PRODUCT)
-- ============================================================
CREATE TABLE MAINTENANCE_LOG (
    product_id       INT           NOT NULL,
    maintenance_date DATE          NOT NULL,
    description      TEXT,
    cost             DECIMAL(10,2) NOT NULL CHECK (cost >= 0),
    CONSTRAINT pk_maintenance PRIMARY KEY (product_id, maintenance_date),
    CONSTRAINT fk_ml_product  FOREIGN KEY (product_id)
        REFERENCES PRODUCT(product_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================================================
-- CUSTOMER (Strong entity — composite address, multi-value email)
-- ============================================================
CREATE TABLE CUSTOMER (
    customer_id  INT AUTO_INCREMENT,
    name         VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20)  NOT NULL,
    email        VARCHAR(100),
    street       VARCHAR(120),
    city         VARCHAR(60),
    postal_code  VARCHAR(12),
    province     VARCHAR(60),
    CONSTRAINT pk_customer PRIMARY KEY (customer_id)
);

-- ============================================================
-- USERS (Auth table — 1-to-1 with CUSTOMER for customers)
-- ============================================================
CREATE TABLE USERS (
    user_id       INT AUTO_INCREMENT,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    customer_id   INT,
    CONSTRAINT pk_users          PRIMARY KEY (user_id),
    CONSTRAINT fk_users_customer FOREIGN KEY (customer_id)
        REFERENCES CUSTOMER(customer_id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

-- ============================================================
-- RECEIPT (Strong entity)
-- ============================================================
CREATE TABLE RECEIPT (
    receipt_id  INT AUTO_INCREMENT,
    customer_id INT           NOT NULL,
    total_price DECIMAL(10,2) NOT NULL CHECK (total_price >= 0),
    issue_date  DATE          NOT NULL,
    CONSTRAINT pk_receipt          PRIMARY KEY (receipt_id),
    CONSTRAINT fk_receipt_customer FOREIGN KEY (customer_id)
        REFERENCES CUSTOMER(customer_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================================================
-- PAYMENT (Strong entity — 1-to-1 with RECEIPT)
-- ============================================================
CREATE TABLE PAYMENT (
    payment_id         INT AUTO_INCREMENT,
    customer_id        INT           NOT NULL,
    receipt_id         INT           NOT NULL,
    payment_method     VARCHAR(50),
    client_card_paypal VARCHAR(100),
    amount             DECIMAL(10,2) NOT NULL CHECK (amount >= 0),
    CONSTRAINT pk_payment          PRIMARY KEY (payment_id),
    CONSTRAINT uq_payment_receipt  UNIQUE (receipt_id),
    CONSTRAINT fk_payment_customer FOREIGN KEY (customer_id)
        REFERENCES CUSTOMER(customer_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_payment_receipt  FOREIGN KEY (receipt_id)
        REFERENCES RECEIPT(receipt_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================================================
-- RENTAL (Strong entity — computed total_days column)
-- ============================================================
CREATE TABLE RENTAL (
    rental_id       INT AUTO_INCREMENT,
    customer_id     INT  NOT NULL,
    receipt_id      INT,
    rental_date     DATE NOT NULL,
    rental_end_date DATE NOT NULL,
    total_days      INT GENERATED ALWAYS AS (DATEDIFF(rental_end_date, rental_date)) STORED,
    CONSTRAINT pk_rental          PRIMARY KEY (rental_id),
    CONSTRAINT fk_rental_customer FOREIGN KEY (customer_id)
        REFERENCES CUSTOMER(customer_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_rental_receipt  FOREIGN KEY (receipt_id)
        REFERENCES RECEIPT(receipt_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_rental_dates   CHECK (rental_end_date >= rental_date)
);

-- ============================================================
-- RENTAL_ITEM (Associative/weak — N-to-M between RENTAL and PRODUCT)
-- ============================================================
CREATE TABLE RENTAL_ITEM (
    rental_id   INT           NOT NULL,
    product_id  INT           NOT NULL,
    quantity    INT           NOT NULL DEFAULT 1 CHECK (quantity > 0),
    rental_rate DECIMAL(10,2) NOT NULL CHECK (rental_rate >= 0),
    CONSTRAINT pk_rental_item         PRIMARY KEY (rental_id, product_id),
    CONSTRAINT fk_ri_rental           FOREIGN KEY (rental_id)
        REFERENCES RENTAL(rental_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ri_product          FOREIGN KEY (product_id)
        REFERENCES PRODUCT(product_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- VIEWS
-- ============================================================
CREATE OR REPLACE VIEW vw_active_rentals AS
    SELECT r.rental_id, c.name AS customer_name, c.email,
           r.rental_date, r.rental_end_date, r.total_days,
           rec.total_price
    FROM RENTAL r
    JOIN CUSTOMER c  ON r.customer_id = c.customer_id
    LEFT JOIN RECEIPT rec ON r.receipt_id = rec.receipt_id
    WHERE r.rental_end_date >= CURDATE();

CREATE OR REPLACE VIEW vw_revenue_by_customer AS
    SELECT c.customer_id, c.name,
           COUNT(DISTINCT r.rental_id)   AS total_rentals,
           COALESCE(SUM(p.amount), 0)    AS total_paid
    FROM CUSTOMER c
    LEFT JOIN RENTAL r  ON c.customer_id = r.customer_id
    LEFT JOIN PAYMENT p ON c.customer_id = p.customer_id
    GROUP BY c.customer_id, c.name;

CREATE OR REPLACE VIEW vw_product_rental_count AS
    SELECT p.product_id, p.type, p.size,
           COUNT(ri.rental_id) AS times_rented,
           m.name AS manufacturer
    FROM PRODUCT p
    LEFT JOIN RENTAL_ITEM ri ON p.product_id = ri.product_id
    LEFT JOIN MANUFACTURER_DISTRIBUTOR m ON p.manufacturer_id = m.manufacturer_id
    GROUP BY p.product_id;

-- ============================================================
-- TRIGGERS
-- ============================================================
DELIMITER $$

-- Trigger: prevent deleting a product that has active rentals
CREATE TRIGGER trg_prevent_product_delete
BEFORE DELETE ON PRODUCT
FOR EACH ROW
BEGIN
    DECLARE cnt INT;
    SELECT COUNT(*) INTO cnt
    FROM RENTAL_ITEM ri
    JOIN RENTAL r ON ri.rental_id = r.rental_id
    WHERE ri.product_id = OLD.product_id
      AND r.rental_end_date >= CURDATE();
    IF cnt > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete product: it has active rentals.';
    END IF;
END$$

-- Trigger: log stock change when rental item is inserted
CREATE TRIGGER trg_decrease_stock
AFTER INSERT ON RENTAL_ITEM
FOR EACH ROW
BEGIN
    UPDATE PRODUCT SET stock = stock - NEW.quantity WHERE product_id = NEW.product_id;
END$$

-- Trigger: restore stock when rental item is deleted
CREATE TRIGGER trg_restore_stock
AFTER DELETE ON RENTAL_ITEM
FOR EACH ROW
BEGIN
    UPDATE PRODUCT SET stock = stock + OLD.quantity WHERE product_id = OLD.product_id;
END$$

-- ============================================================
-- STORED PROCEDURES
-- ============================================================

CREATE PROCEDURE sp_create_rental(
    IN  p_customer_id    INT,
    IN  p_rental_date    DATE,
    IN  p_end_date       DATE,
    IN  p_payment_method VARCHAR(50),
    IN  p_card_paypal    VARCHAR(100),
    OUT p_rental_id      INT,
    OUT p_receipt_id     INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
    INSERT INTO RECEIPT (customer_id, total_price, issue_date)
        VALUES (p_customer_id, 0.00, CURDATE());
    SET p_receipt_id = LAST_INSERT_ID();
    INSERT INTO RENTAL (customer_id, receipt_id, rental_date, rental_end_date)
        VALUES (p_customer_id, p_receipt_id, p_rental_date, p_end_date);
    SET p_rental_id = LAST_INSERT_ID();
    COMMIT;
END$$

CREATE PROCEDURE sp_update_receipt_total(IN p_receipt_id INT)
BEGIN
    DECLARE v_total DECIMAL(10,2);
    SELECT COALESCE(SUM(ri.rental_rate * ri.quantity * DATEDIFF(r.rental_end_date, r.rental_date)), 0)
    INTO v_total
    FROM RENTAL_ITEM ri
    JOIN RENTAL r ON ri.rental_id = r.rental_id
    WHERE r.receipt_id = p_receipt_id;
    UPDATE RECEIPT SET total_price = v_total WHERE receipt_id = p_receipt_id;
END$$

-- ============================================================
-- STORED FUNCTION
-- ============================================================

CREATE FUNCTION fn_customer_total_days(p_customer_id INT)
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_days INT;
    SELECT COALESCE(SUM(total_days), 0) INTO v_days
    FROM RENTAL WHERE customer_id = p_customer_id;
    RETURN v_days;
END$$

DELIMITER ;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

INSERT INTO MANUFACTURER_DISTRIBUTOR (name, country, contact_email, contact_phone) VALUES
    ('Stentor Music Co.',  'United Kingdom', 'contact@stentor.co.uk', '+44-800-123-456'),
    ('Yamaha Corporation', 'Japan',          'support@yamaha.com',    '+81-3-5488-6000'),
    ('Franz Hoffmann',     'Germany',        'info@franzhoffmann.com','+49-30-1234567'),
    ('Eastman Strings',    'USA',            'info@eastmanmusic.com', '+1-626-350-9635');

INSERT INTO PRODUCT (type, size, price, stock, manufacturer_id) VALUES
    ('Violin', '1/4',  5.00, 8, 1),
    ('Violin', '1/2',  6.50, 6, 1),
    ('Violin', '3/4',  7.00, 5, 2),
    ('Violin', '4/4',  9.00, 4, 4),
    ('Bow',    'Full', 2.00, 10,3),
    ('Rosin',  NULL,   0.50, 20,3),
    ('Case',   '4/4',  1.50, 7, 2),
    ('Shoulder Rest', NULL, 1.00, 12, 3);

INSERT INTO CUSTOMER (name, phone_number, email, street, city, postal_code, province) VALUES
    ('Alice Johnson', '403-555-0101', 'alice@email.com',  '12 Elm St',   'Lethbridge','T1H 1A1','Alberta'),
    ('Bob Smith',     '403-555-0102', 'bob@email.com',    '34 Oak Ave',  'Calgary',   'T2P 1B2','Alberta'),
    ('Carol White',   '403-555-0103', 'carol@email.com',  '56 Pine Rd',  'Edmonton',  'T5J 2C3','Alberta'),
    ('David Lee',     '403-555-0104', 'david@email.com',  '78 Maple Dr', 'Lethbridge','T1K 3D4','Alberta'),
    ('Eva Brown',     '403-555-0105', 'eva@email.com',    '90 Birch Blvd','Red Deer', 'T4N 4E5','Alberta');

INSERT INTO USERS (username, password_hash, role, customer_id) VALUES
    ('admin', '$2y$10$placeholder', 'admin',    NULL),
    ('alice', '$2y$10$placeholder', 'customer', 1),
    ('bob',   '$2y$10$placeholder', 'customer', 2),
    ('carol', '$2y$10$placeholder', 'customer', 3),
    ('david', '$2y$10$placeholder', 'customer', 4),
    ('eva',   '$2y$10$placeholder', 'customer', 5);

INSERT INTO RECEIPT (customer_id, total_price, issue_date) VALUES
    (1, 63.00, '2026-01-10'),
    (2, 27.00, '2026-01-15'),
    (3, 45.50, '2026-02-01'),
    (4, 18.00, '2026-02-10'),
    (5, 90.00, '2026-03-01');

INSERT INTO PAYMENT (customer_id, receipt_id, payment_method, client_card_paypal, amount) VALUES
    (1, 1, 'card',   'VISA-4111',      63.00),
    (2, 2, 'paypal', 'bob@paypal.com', 27.00),
    (3, 3, 'cash',   NULL,             45.50),
    (4, 4, 'card',   'MC-5500',        18.00),
    (5, 5, 'paypal', 'eva@paypal.com', 90.00);

INSERT INTO RENTAL (customer_id, receipt_id, rental_date, rental_end_date) VALUES
    (1, 1, '2026-01-10', '2026-04-10'),
    (2, 2, '2026-01-15', '2026-02-15'),
    (3, 3, '2026-02-01', '2026-05-01'),
    (4, 4, '2026-02-10', '2026-03-10'),
    (5, 5, '2026-03-01', '2026-06-01');

INSERT INTO RENTAL_ITEM (rental_id, product_id, quantity, rental_rate) VALUES
    (1, 1, 1, 5.00),(1, 5, 1, 2.00),
    (2, 3, 1, 7.00),(2, 6, 2, 0.50),
    (3, 4, 1, 9.00),(3, 7, 1, 1.50),
    (4, 2, 1, 6.50),
    (5, 4, 1, 9.00),(5, 5, 1, 2.00),(5, 8, 1, 1.00);

INSERT INTO MAINTENANCE_LOG (product_id, maintenance_date, description, cost) VALUES
    (1, '2025-12-01', 'Bow rehair and bridge adjustment',  15.00),
    (3, '2026-01-05', 'Full setup and string replacement', 30.00),
    (4, '2026-01-20', 'Crack repair on top plate',         75.00),
    (2, '2026-02-15', 'Pegs replaced and fitted',          20.00);