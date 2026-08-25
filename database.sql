-- ============================================
-- DATABASE: autodealer
-- Version: 1.0.0
-- Description: Full database for AutoDealer website
-- Integration: 100% compatible with all PHP files
-- ============================================

-- Drop database if exists (optional - comment out if you want to keep data)
-- DROP DATABASE IF EXISTS autodealer;

CREATE DATABASE IF NOT EXISTS autodealer;
USE autodealer;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE DEFAULT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('owner', 'supervisor', 'marketing', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME DEFAULT NULL,
    remember_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. ROLES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    permissions JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. BRANDS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS brands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. PRODUCTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    brand_id INT NOT NULL,
    model VARCHAR(100) NOT NULL,
    variant VARCHAR(100) DEFAULT NULL,
    slug VARCHAR(150) UNIQUE NOT NULL,
    year INT DEFAULT NULL,
    price DECIMAL(15,2) NOT NULL,
    promo_price DECIMAL(15,2) DEFAULT NULL,
    mileage INT DEFAULT NULL,
    transmission ENUM('Manual', 'Automatic', 'CVT', 'DCT') DEFAULT 'Manual',
    fuel_type ENUM('Bensin', 'Diesel', 'Electric', 'Hybrid') DEFAULT 'Bensin',
    color VARCHAR(50) DEFAULT NULL,
    `condition` ENUM('Baru', 'Bekas', 'Reconditioned') DEFAULT 'Baru',
    stock INT DEFAULT 1,
    vin VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    specifications JSON DEFAULT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    is_promo BOOLEAN DEFAULT FALSE,
    status ENUM('available', 'sold', 'reserved', 'inactive') DEFAULT 'available',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    INDEX idx_brand (brand_id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_price (price),
    INDEX idx_year (year),
    INDEX idx_featured (is_featured),
    INDEX idx_promo (is_promo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. PRODUCT IMAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_primary (is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. CUSTOMERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(50) DEFAULT NULL,
    province VARCHAR(50) DEFAULT NULL,
    postal_code VARCHAR(10) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    marketing_id INT DEFAULT NULL,
    source VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (marketing_id) REFERENCES marketing(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_marketing (marketing_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. MARKETING TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS marketing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    target_sales INT DEFAULT 0,
    commission_rate DECIMAL(5,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. PASSWORD RESETS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. CARTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS carts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. CART ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_cart (cart_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. ORDERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    marketing_id INT DEFAULT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0,
    final_amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'credit', 'transfer') DEFAULT 'cash',
    down_payment DECIMAL(15,2) DEFAULT NULL,
    installment_tenor INT DEFAULT NULL,
    status ENUM('pending', 'processing', 'waiting_payment', 'verified', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (marketing_id) REFERENCES marketing(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_date (order_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. ORDER ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. TRANSACTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_number VARCHAR(30) UNIQUE NOT NULL,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'credit', 'transfer', 'bank_transfer') NOT NULL,
    payment_status ENUM('pending', 'paid', 'partial', 'failed', 'refunded', 'completed') DEFAULT 'pending',
    payment_proof VARCHAR(255) DEFAULT NULL,
    bank_account VARCHAR(50) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_transaction_number (transaction_number),
    INDEX idx_order (order_id),
    INDEX idx_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. OFFERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    offer_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    marketing_id INT DEFAULT NULL,
    price DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0,
    final_price DECIMAL(15,2) NOT NULL,
    notes TEXT DEFAULT NULL,
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired') DEFAULT 'draft',
    valid_until DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (marketing_id) REFERENCES marketing(id) ON DELETE SET NULL,
    INDEX idx_offer_number (offer_number),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. WEBSITE SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS website_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key),
    INDEX idx_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 16. ADVERTISEMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS advertisements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    script_code TEXT DEFAULT NULL,
    position ENUM('head', 'body', 'footer') DEFAULT 'body',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 17. ACTIVITY LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    username VARCHAR(50) DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 18. NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT DEFAULT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 19. ADD FOREIGN KEY FOR CUSTOMERS (after marketing table exists)
-- ============================================
-- Note: Foreign key for customers.marketing_id already added in customers table creation
-- This is just a re-verification

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- ------------------------------------------------------------------------
-- 1. INSERT ROLES
-- ------------------------------------------------------------------------
INSERT IGNORE INTO roles (id, name, description) VALUES 
(1, 'owner', 'Full access to all system features including settings, users, and advertising'),
(2, 'supervisor', 'Management access except system settings and user management'),
(3, 'marketing', 'Sales and customer management access only');

-- ------------------------------------------------------------------------
-- 2. INSERT ADMIN USER
-- Username: admindeveloper
-- Email: admindeveloper@autodealer.com
-- Password: Rohman0037 (hashed using password_hash)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ------------------------------------------------------------------------
INSERT IGNORE INTO users (
    username, 
    email, 
    password, 
    first_name, 
    last_name, 
    role, 
    status,
    created_at
) VALUES (
    'admindeveloper',
    'admindeveloper@autodealer.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin',
    'Developer',
    'owner',
    'active',
    NOW()
);

-- ------------------------------------------------------------------------
-- 3. INSERT WEBSITE SETTINGS
-- ------------------------------------------------------------------------
INSERT IGNORE INTO website_settings (setting_key, setting_value, setting_group, is_public) VALUES
-- General Settings
('website_name', 'AutoDealer', 'general', 1),
('website_tagline', 'Premium Car Dealer', 'general', 1),
('website_description', 'Your trusted premium car dealer with best quality vehicles', 'general', 1),
('website_logo', '', 'general', 1),
('favicon', '', 'general', 1),

-- Contact Settings
('website_phone', '+6281234567890', 'contact', 1),
('website_whatsapp', '+6281234567890', 'contact', 1),
('website_email', 'info@autodealer.com', 'contact', 1),
('website_address', 'Jl. Raya No. 123, Jakarta Selatan, Indonesia', 'contact', 1),
('website_opening_hours', 'Senin - Sabtu: 08:00 - 20:00 WIB', 'contact', 1),

-- Social Media
('website_instagram', 'autodealer_official', 'social', 1),
('website_facebook', 'autodealer.official', 'social', 1),
('website_tiktok', '@autodealer', 'social', 1),
('website_youtube', '@autodealer', 'social', 1),

-- Design Settings
('website_primary_color', '#1a2332', 'design', 1),
('website_secondary_color', '#c9a84c', 'design', 1),
('website_currency', 'Rp', 'general', 1),
('website_footer_text', '© 2026 AutoDealer. All Rights Reserved.', 'general', 1),

-- SEO Settings
('meta_title', 'AutoDealer - Premium Car Dealer Terpercaya', 'seo', 1),
('meta_description', 'Temukan mobil impian Anda di AutoDealer. Koleksi mobil premium dengan harga terbaik dan pelayanan profesional.', 'seo', 1),
('meta_keywords', 'dealer mobil, mobil premium, auto dealer, jual mobil, mobil baru, mobil bekas', 'seo', 1),
('meta_author', 'AutoDealer', 'seo', 1),
('og_image', '', 'seo', 1),
('google_verification', '', 'seo', 1),
('robots', 'index, follow', 'seo', 1),
('sitemap_enabled', '1', 'seo', 1),

-- Advertising/Tracking
('google_analytics', '', 'advertising', 0),
('google_ads', '', 'advertising', 0),
('google_ads_conversion', '', 'advertising', 0),
('meta_pixel', '', 'advertising', 0),
('ga4_measurement_id', '', 'advertising', 0),
('custom_head', '', 'advertising', 0),
('custom_body', '', 'advertising', 0),
('custom_footer', '', 'advertising', 0),

-- Bank Accounts
('bank_bca', 'BCA: 1234567890 a.n. AutoDealer', 'payment', 1),
('bank_mandiri', 'Mandiri: 9876543210 a.n. AutoDealer', 'payment', 1),
('bank_bni', 'BNI: 1122334455 a.n. AutoDealer', 'payment', 1);

-- ------------------------------------------------------------------------
-- 4. INSERT SAMPLE BRANDS
-- ------------------------------------------------------------------------
INSERT IGNORE INTO brands (name, slug, description, status, sort_order) VALUES
('Toyota', 'toyota', 'Toyota Motor Corporation - Japanese automotive manufacturer known for reliability and innovation', 'active', 1),
('Honda', 'honda', 'Honda Motor Co., Ltd. - Japanese automotive manufacturer known for performance and quality', 'active', 2),
('Mitsubishi', 'mitsubishi', 'Mitsubishi Motors Corporation - Japanese automotive manufacturer', 'active', 3),
('Suzuki', 'suzuki', 'Suzuki Motor Corporation - Japanese automotive manufacturer known for compact cars', 'active', 4),
('Daihatsu', 'daihatsu', 'Daihatsu Motor Co., Ltd. - Japanese automotive manufacturer known for small cars', 'active', 5),
('Nissan', 'nissan', 'Nissan Motor Corporation - Japanese automotive manufacturer', 'active', 6),
('Hyundai', 'hyundai', 'Hyundai Motor Company - South Korean automotive manufacturer', 'active', 7),
('BMW', 'bmw', 'Bayerische Motoren Werke AG - German luxury automotive manufacturer', 'active', 8),
('Mercedes-Benz', 'mercedes-benz', 'Mercedes-Benz Group AG - German luxury automotive manufacturer', 'active', 9),
('Kia', 'kia', 'Kia Corporation - South Korean automotive manufacturer', 'active', 10);

-- ------------------------------------------------------------------------
-- 5. INSERT SAMPLE PRODUCTS
-- ------------------------------------------------------------------------
INSERT IGNORE INTO products (
    brand_id, model, variant, slug, year, price, promo_price, 
    mileage, transmission, fuel_type, color, `condition`, stock, 
    description, is_featured, is_promo, status
) VALUES
-- Toyota
(1, 'Avanza', '1.3 E M/T', 'toyota-avanza-1-3-e-mt-2026', 2026, 250000000, 235000000, 0, 'Manual', 'Bensin', 'Silver Metallic', 'Baru', 5, 
 'Toyota Avanza 1.3 E M/T - Mobil keluarga 7 seater yang nyaman, irit bahan bakar, dan handal untuk segala kondisi jalan. Dilengkapi dengan fitur keselamatan lengkap dan teknologi modern.', 1, 1, 'available'),

(1, 'Innova', '2.0 G A/T', 'toyota-innova-2-0-g-at-2026', 2026, 380000000, 360000000, 0, 'Automatic', 'Bensin', 'White Pearl', 'Baru', 3, 
 'Toyota Innova 2.0 G A/T - MPV premium dengan desain elegan, kabin luas, dan performa mesin yang tangguh. Cocok untuk perjalanan keluarga dan bisnis.', 1, 0, 'available'),

(1, 'Yaris', '1.5 G CVT', 'toyota-yaris-1-5-g-cvt-2026', 2026, 320000000, 0, 0, 'CVT', 'Bensin', 'Red Mica', 'Baru', 4, 
 'Toyota Yaris 1.5 G CVT - Hatchback sporty dengan desain modern, handling responsif, dan konsumsi bahan bakar efisien. Ideal untuk gaya hidup perkotaan.', 0, 0, 'available'),

-- Honda
(2, 'HR-V', '1.5 E CVT', 'honda-hr-v-1-5-e-cvt-2026', 2026, 350000000, 330000000, 0, 'CVT', 'Bensin', 'Red', 'Baru', 4, 
 'Honda HR-V 1.5 E CVT - SUV compact dengan desain sporty, kabin premium, dan teknologi canggih. Menawarkan kenyamanan berkendara yang luar biasa.', 1, 1, 'available'),

(2, 'Civic', '1.8 RS CVT', 'honda-civic-1-8-rs-cvt-2026', 2026, 450000000, 425000000, 0, 'CVT', 'Bensin', 'Black', 'Baru', 2, 
 'Honda Civic 1.8 RS CVT - Sedan sporty dengan performa tinggi, desain aerodinamis, dan fitur keselamatan mutakhir. Pilihan tepat untuk pecinta kecepatan.', 1, 0, 'available'),

(2, 'Brio', '1.2 S M/T', 'honda-brio-1-2-s-mt-2026', 2026, 180000000, 170000000, 0, 'Manual', 'Bensin', 'White', 'Baru', 8, 
 'Honda Brio 1.2 S M/T - City car kompak dengan desain stylish, handling lincah, dan irit bahan bakar. Solusi transportasi perkotaan yang praktis.', 0, 1, 'available'),

-- Mitsubishi
(3, 'Xpander', '1.5 A/T', 'mitsubishi-xpander-1-5-at-2026', 2026, 280000000, 265000000, 0, 'Automatic', 'Bensin', 'Blue', 'Baru', 6, 
 'Mitsubishi Xpander 1.5 A/T - MPV modern dengan desain bold, interior fleksibel, dan performa handal. Pilihan keluarga yang stylish dan fungsional.', 0, 1, 'available'),

(3, 'Pajero Sport', '2.4 A/T', 'mitsubishi-pajero-sport-2-4-at-2026', 2026, 550000000, 520000000, 0, 'Automatic', 'Diesel', 'Gray', 'Baru', 2, 
 'Mitsubishi Pajero Sport 2.4 A/T - SUV premium dengan mesin diesel bertenaga, pengendaraan off-road yang mumpuni, dan kabin mewah. Untuk petualangan sejati.', 0, 0, 'available'),

-- Suzuki
(4, 'Ertiga', '1.5 A/T', 'suzuki-ertiga-1-5-at-2026', 2026, 230000000, 0, 0, 'Automatic', 'Bensin', 'Silver', 'Baru', 5, 
 'Suzuki Ertiga 1.5 A/T - MPV 7 seater yang irit, nyaman, dan praktis. Dilengkapi dengan fitur keselamatan dan hiburan untuk keluarga.', 0, 0, 'available'),

-- Daihatsu
(5, 'Ayla', '1.0 M/T', 'daihatsu-ayla-1-0-mt-2026', 2026, 150000000, 0, 0, 'Manual', 'Bensin', 'White', 'Baru', 8, 
 'Daihatsu Ayla 1.0 M/T - City car kompak dengan desain cute, efisiensi bahan bakar tinggi, dan harga terjangkau. Pilihan cerdas untuk pemula dan urban.', 0, 0, 'available'),

(5, 'Terios', '1.5 R A/T', 'daihatsu-terios-1-5-r-at-2026', 2026, 270000000, 250000000, 0, 'Automatic', 'Bensin', 'Black', 'Baru', 4, 
 'Daihatsu Terios 1.5 R A/T - SUV compact dengan ground clearance tinggi, performa tangguh, dan desain yang gagah. Siap menemani petualangan Anda.', 0, 1, 'available'),

-- BMW
(8, 'X5', 'xDrive40i', 'bmw-x5-xdrive40i-2026', 2026, 2500000000, 2350000000, 0, 'Automatic', 'Bensin', 'Black Sapphire', 'Baru', 2, 
 'BMW X5 xDrive40i - SUV mewah dengan mesin 3.0L turbo, sistem penggerak all-wheel drive, dan interior premium. Kemewahan dan performa dalam satu paket.', 1, 0, 'available');

-- ------------------------------------------------------------------------
-- 6. INSERT SAMPLE PRODUCT IMAGES (will be used as examples)
-- ------------------------------------------------------------------------
-- Note: Actual images need to be uploaded separately
-- These are placeholder records for the structure
INSERT IGNORE INTO product_images (product_id, image_path, is_primary, sort_order) VALUES
(1, 'sample-avanza-1.jpg', 1, 0),
(1, 'sample-avanza-2.jpg', 0, 1),
(4, 'sample-hrv-1.jpg', 1, 0),
(5, 'sample-civic-1.jpg', 1, 0),
(7, 'sample-xpander-1.jpg', 1, 0);

-- ------------------------------------------------------------------------
-- 7. INSERT SAMPLE CUSTOMER
-- ------------------------------------------------------------------------
INSERT IGNORE INTO customers (full_name, email, phone, address, status, created_at) VALUES
('Budi Santoso', 'budi@email.com', '081234567891', 'Jl. Melati No. 10, Jakarta', 'active', NOW()),
('Siti Rahayu', 'siti@email.com', '081234567892', 'Jl. Kenanga No. 5, Bandung', 'active', NOW()),
('Ahmad Fauzi', 'ahmad@email.com', '081234567893', 'Jl. Mawar No. 20, Surabaya', 'active', NOW());

-- ------------------------------------------------------------------------
-- 8. INSERT SAMPLE MARKETING
-- ------------------------------------------------------------------------
INSERT IGNORE INTO marketing (user_id, full_name, email, phone, target_sales, commission_rate, status) VALUES
(1, 'Marketing Team', 'marketing@autodealer.com', '081234567899', 100, 5.00, 'active');

-- ------------------------------------------------------------------------
-- 9. INSERT SAMPLE ORDERS
-- ------------------------------------------------------------------------
INSERT IGNORE INTO orders (order_number, customer_id, marketing_id, total_amount, discount, final_amount, payment_method, status, order_date) VALUES
('ORD-20260825-0001', 1, 1, 250000000, 15000000, 235000000, 'cash', 'completed', '2026-08-25 10:00:00'),
('ORD-20260826-0002', 2, 1, 350000000, 20000000, 330000000, 'transfer', 'verified', '2026-08-26 14:30:00'),
('ORD-20260827-0003', 3, 1, 280000000, 15000000, 265000000, 'credit', 'pending', '2026-08-27 09:15:00');

-- ------------------------------------------------------------------------
-- 10. INSERT SAMPLE ORDER ITEMS
-- ------------------------------------------------------------------------
INSERT IGNORE INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 235000000),
(2, 4, 1, 330000000),
(3, 7, 1, 265000000);

-- ------------------------------------------------------------------------
-- 11. INSERT SAMPLE TRANSACTIONS
-- ------------------------------------------------------------------------
INSERT IGNORE INTO transactions (transaction_number, order_id, customer_id, amount, payment_method, payment_status, transaction_date) VALUES
('TRX-20260825-0001', 1, 1, 235000000, 'cash', 'completed', '2026-08-25 10:30:00'),
('TRX-20260826-0002', 2, 2, 330000000, 'bank_transfer', 'paid', '2026-08-26 15:00:00');

-- ------------------------------------------------------------------------
-- 12. INSERT SAMPLE OFFERS
-- ------------------------------------------------------------------------
INSERT IGNORE INTO offers (offer_number, customer_id, product_id, marketing_id, price, discount, final_price, status, valid_until) VALUES
('OFF-20260825-0001', 1, 2, 1, 380000000, 20000000, 360000000, 'sent', DATE_ADD(NOW(), INTERVAL 7 DAY)),
('OFF-20260826-0002', 2, 5, 1, 450000000, 25000000, 425000000, 'accepted', DATE_ADD(NOW(), INTERVAL 7 DAY));

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Check admin user
-- SELECT * FROM users WHERE username = 'admindeveloper';

-- Check admin user with password verification
-- SELECT id, username, email, password, role, status 
-- FROM users WHERE username = 'admindeveloper';
-- Expected: 1 row with role='owner', status='active'

-- Check website settings
-- SELECT setting_key, setting_value FROM website_settings WHERE is_public = 1;

-- Check all tables
-- SHOW TABLES;

-- ============================================
-- END OF DATABASE.SQL
-- ============================================
