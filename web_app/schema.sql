-- Butyrate Pathway Explorer Database Schema
-- Database: digitaltwin
-- Host: mysql.newrality.com

-- Create sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create selected_reactions table
CREATE TABLE IF NOT EXISTS selected_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    reaction_id VARCHAR(32) NOT NULL,
    reaction_name TEXT,
    subsystem VARCHAR(255),
    phase VARCHAR(32),
    ensg_list TEXT,
    selected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_reaction_id (reaction_id),
    UNIQUE KEY unique_session_reaction (session_id, reaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create fba_results table
CREATE TABLE IF NOT EXISTS fba_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active_reactions INT DEFAULT 0,
    total_flux DECIMAL(10,4) DEFAULT 0.0,
    results_json TEXT,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_run_at (run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments
ALTER TABLE sessions COMMENT='User sessions for Butyrate Pathway Explorer';
ALTER TABLE selected_reactions COMMENT='Reactions selected by each session';
ALTER TABLE fba_results COMMENT='FBA validation results per session';
