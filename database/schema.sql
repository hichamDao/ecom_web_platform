-- =====================================================================
--  EcomWeb Platform - Schéma de la base de données MySQL
--  Import : mysql -u root < database/schema.sql
--  (ou via phpMyAdmin > Importer)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS ecom_platform
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ecom_platform;

-- ---------------------------------------------------------------------
-- Utilisateurs
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username    VARCHAR(80)  NOT NULL,
  email       VARCHAR(160) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  first_name  VARCHAR(80)  NULL,
  last_name   VARCHAR(80)  NULL,
  phone       VARCHAR(40)  NULL,
  avatar      VARCHAR(255) NULL,
  role        ENUM('CUSTOMER','ADMIN') NOT NULL DEFAULT 'CUSTOMER',
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at DATETIME   NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catégories (hiérarchie parent/enfant)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(120) NOT NULL,
  description TEXT         NULL,
  image       VARCHAR(255) NULL,
  parent_id   INT UNSIGNED NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_categories_parent (parent_id),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id)
    REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Produits
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name                 VARCHAR(200) NOT NULL,
  description          TEXT         NULL,
  price                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  old_price            DECIMAL(10,2) NULL,
  discount_percentage  DECIMAL(5,2)  NULL,
  stock                INT          NOT NULL DEFAULT 0,
  thumbnail            VARCHAR(255) NULL,
  images               LONGTEXT     NULL,  -- JSON: ["url1", "url2"]
  attributes           LONGTEXT     NULL,  -- JSON: {"color": "noir"}
  brand                VARCHAR(120) NULL,
  is_featured          TINYINT(1)   NOT NULL DEFAULT 0,
  is_new               TINYINT(1)   NOT NULL DEFAULT 0,
  overlay_image        VARCHAR(255) NULL,  -- image essayage virtuel
  category_id          INT UNSIGNED NULL,
  sold_count           INT UNSIGNED NOT NULL DEFAULT 0,
  views                INT UNSIGNED NOT NULL DEFAULT 0,
  rating_cache         DECIMAL(3,2)  NOT NULL DEFAULT 0.00,
  review_count_cache   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_products_category (category_id),
  KEY idx_products_featured (is_featured),
  KEY idx_products_new (is_new),
  KEY idx_products_price (price),
  KEY idx_products_created (created_at),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Avis clients
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id           INT UNSIGNED NOT NULL,
  user_id              INT UNSIGNED NULL,
  rating               DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  comment              TEXT         NULL,
  images               LONGTEXT     NULL, -- JSON: ["url1"]
  status               ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  is_verified_purchase TINYINT(1)   NOT NULL DEFAULT 0,
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reviews_product (product_id),
  KEY idx_reviews_user (user_id),
  KEY idx_reviews_status (status),
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Listes de wishes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wishlists (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wishlist (user_id, product_id),
  CONSTRAINT fk_wishlists_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlists_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Adresses de livraison
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS addresses (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  full_name  VARCHAR(160) NOT NULL,
  phone      VARCHAR(40)  NULL,
  address    VARCHAR(255) NOT NULL,
  city       VARCHAR(120) NOT NULL,
  state      VARCHAR(120) NULL,
  zip_code   VARCHAR(20)  NULL,
  country    VARCHAR(120) NOT NULL DEFAULT 'Maroc',
  is_default TINYINT(1)   NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_addresses_user (user_id),
  CONSTRAINT fk_addresses_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Paniers
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS carts (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  coupon_code     VARCHAR(60)  NULL,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carts_user (user_id),
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart_items (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id        INT UNSIGNED NOT NULL,
  product_id     INT UNSIGNED NOT NULL,
  quantity       INT UNSIGNED NOT NULL DEFAULT 1,
  selected_size  VARCHAR(40)  NULL,
  selected_color VARCHAR(40)  NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cart_items_cart (cart_id),
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id)
    REFERENCES carts (id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Codes promo
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code                VARCHAR(60)  NOT NULL,
  percentage          DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  min_order_amount    DECIMAL(10,2) NULL,
  max_discount_amount DECIMAL(10,2) NULL,
  usage_limit         INT UNSIGNED NULL,
  used_count          INT UNSIGNED NOT NULL DEFAULT 0,
  product_ids         LONGTEXT     NULL, -- JSON: [1,2,3] restriction produits
  user_id             INT UNSIGNED NULL, -- code nominatif (null = global)
  expires_at          DATETIME     NULL,
  is_active           TINYINT(1)   NOT NULL DEFAULT 1,
  created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_coupons_code (code),
  KEY idx_coupons_active (is_active),
  CONSTRAINT fk_coupons_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Commandes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_number      VARCHAR(30)  NOT NULL,
  user_id           INT UNSIGNED NULL,
  customer_email    VARCHAR(160) NULL,
  subtotal          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  shipping_fee      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount_amount   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status            ENUM('PENDING','CONFIRMED','PROCESSING','SHIPPED','DELIVERED','CANCELLED','REFUNDED')
                    NOT NULL DEFAULT 'PENDING',
  is_paid           TINYINT(1)   NOT NULL DEFAULT 0,
  payment_method    VARCHAR(40)  NULL,
  tracking_number   VARCHAR(60)  NULL,
  notes             TEXT         NULL,
  ship_full_name    VARCHAR(160) NULL,
  ship_phone        VARCHAR(40)  NULL,
  ship_address      VARCHAR(255) NULL,
  ship_city         VARCHAR(120) NULL,
  ship_state        VARCHAR(120) NULL,
  ship_zip_code     VARCHAR(20)  NULL,
  ship_country      VARCHAR(120) NULL,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_number (order_number),
  KEY idx_orders_user (user_id),
  KEY idx_orders_status (status),
  KEY idx_orders_created (created_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id          INT UNSIGNED NOT NULL,
  product_id        INT UNSIGNED NULL,
  product_name      VARCHAR(200) NOT NULL,
  product_thumbnail VARCHAR(255) NULL,
  quantity          INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  selected_size     VARCHAR(40)  NULL,
  selected_color    VARCHAR(40)  NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_status_history (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id   INT UNSIGNED NOT NULL,
  status     VARCHAR(30)  NOT NULL,
  note       VARCHAR(255) NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_status_history_order (order_id),
  CONSTRAINT fk_status_history_order FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Bannières promotionnelles
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promo_banners (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(160) NOT NULL,
  subtitle    VARCHAR(255) NULL,
  photo       VARCHAR(255) NULL,
  link        VARCHAR(255) NULL,
  button_text VARCHAR(60)  NOT NULL DEFAULT 'Découvrir',
  color_hex   VARCHAR(20)  NULL,
  position    INT UNSIGNED NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_banners_active (is_active, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Notifications (user_id NULL = diffusion à tous)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,
  type       VARCHAR(60)  NOT NULL DEFAULT 'PROMOTION',
  title      VARCHAR(200) NOT NULL,
  message    TEXT         NULL,
  priority   ENUM('HIGH','MEDIUM','LOW') NOT NULL DEFAULT 'MEDIUM',
  is_read    TINYINT(1)   NOT NULL DEFAULT 0,
  data       LONGTEXT     NULL, -- JSON libre
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user (user_id, is_read),
  KEY idx_notifications_created (created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Paramètres clé/valeur
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key   VARCHAR(80)  NOT NULL,
  setting_value TEXT         NULL,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
