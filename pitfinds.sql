-- PIT Finds database schema (MySQL 5.7+ / MariaDB 10.3+)
-- Import with phpMyAdmin (Import tab) or: mysql -u root -p < pitfinds.sql
CREATE DATABASE IF NOT EXISTS `pitfinds` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pitfinds`;

-- Campus members who report and claim items
CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  school_id  VARCHAR(20)  NOT NULL UNIQUE,
  name       VARCHAR(60)  NOT NULL,
  email      VARCHAR(120) NOT NULL UNIQUE,
  pw_hash    VARCHAR(255) NOT NULL,
  type       ENUM('student','faculty','staff') NOT NULL DEFAULT 'student',
  dept       VARCHAR(80)  NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Office staff (separate from users, log in at /admin/login.php)
CREATE TABLE IF NOT EXISTS admins (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(40) NOT NULL UNIQUE,
  name       VARCHAR(60) NOT NULL,
  pw_hash    VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  type       ENUM('lost','found') NOT NULL,
  title      VARCHAR(80)  NOT NULL,
  cat        VARCHAR(30)  NOT NULL,
  place      VARCHAR(160) NOT NULL,           -- campus location, optional exact spot
  seen_on    DATE         NOT NULL,
  descr      TEXT         NOT NULL,
  image      VARCHAR(60)  NULL,
  user_id    INT          NOT NULL,
  status     ENUM('pending','approved','rejected','returned') NOT NULL DEFAULT 'pending',
  note       VARCHAR(255) NOT NULL DEFAULT '',   -- message from the office to the user
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_items_status (status, id),
  INDEX idx_items_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS claims (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  item_id    INT NOT NULL,
  user_id    INT NOT NULL,
  message    TEXT NOT NULL,
  status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  note       VARCHAR(255) NOT NULL DEFAULT '',   -- message from the office to the claimant
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_claims_item (item_id, status),
  INDEX idx_claims_user (user_id),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
-- No rows inserted on purpose: the first page load creates the admin (admin / admin123) with a proper password hash.
