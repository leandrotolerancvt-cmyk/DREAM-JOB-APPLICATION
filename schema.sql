CREATE DATABASE IF NOT EXISTS dream_job_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dream_job_db;

CREATE TABLE IF NOT EXISTS applications (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name        VARCHAR(50)  NOT NULL,
  last_name         VARCHAR(50)  NOT NULL,
  email             VARCHAR(100) NOT NULL,
  phone             VARCHAR(20)  NOT NULL,
  education_level   VARCHAR(50)  NOT NULL,
  years_experience  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  primary_tool      VARCHAR(50)  NOT NULL,
  portfolio_url     VARCHAR(255) DEFAULT NULL,
  expected_salary   DECIMAL(10,2) NOT NULL,
  date_added        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
