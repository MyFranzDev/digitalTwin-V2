-- Migration: Add fba_results table
-- Run this if upgrading from previous schema

CREATE TABLE IF NOT EXISTS fba_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    active_reactions INT,
    total_flux FLOAT,
    results_json LONGTEXT,
    config_json TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_session (session_id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
