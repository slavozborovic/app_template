-- Shoper App Store — Database Schema
--
-- Import this file into your MySQL database:
--   mysql -u USER -p DB_NAME < sql/mysql.sql

CREATE TABLE IF NOT EXISTS shops (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    shop        VARCHAR(128) NOT NULL,
    shop_url    VARCHAR(512) NOT NULL,
    version     INT DEFAULT 1,
    auth_code   CHAR(50),
    installed   SMALLINT DEFAULT 0,
    INDEX idx_shop (shop)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS access_tokens (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    shop_id       INT NOT NULL,
    expires_at    TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    access_token  CHAR(50),
    refresh_token CHAR(50),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    INDEX idx_shop_id (shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS billings (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    shop_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    INDEX idx_shop_id (shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscriptions (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    shop_id    INT NOT NULL,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    INDEX idx_shop_id (shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
