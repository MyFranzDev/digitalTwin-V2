-- Digital Twin V2 - Database Schema
-- Human-GEM metabolic model relational storage

-- Drop existing tables if recreating
DROP TABLE IF EXISTS user_selections;
DROP TABLE IF EXISTS reaction_notes;
DROP TABLE IF EXISTS reaction_annotations;
DROP TABLE IF EXISTS reaction_metabolites;
DROP TABLE IF EXISTS reaction_genes;
DROP TABLE IF EXISTS metabolites;
DROP TABLE IF EXISTS genes;
DROP TABLE IF EXISTS reactions;
DROP TABLE IF EXISTS models;

-- Models table: tracks which metabolic models are imported
CREATE TABLE models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    github_url TEXT,
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_model (name, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactions table: metabolic reactions
CREATE TABLE reactions (
    id VARCHAR(50) NOT NULL,
    model_id INT NOT NULL,
    name TEXT,
    subsystem VARCHAR(255),
    formula TEXT,
    reversibility BOOLEAN,
    lower_bound FLOAT,
    upper_bound FLOAT,
    PRIMARY KEY (id, model_id),
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE,
    FULLTEXT KEY ft_search (name, subsystem),
    KEY idx_subsystem (subsystem),
    KEY idx_reversibility (reversibility)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Genes table: ENSG gene identifiers
CREATE TABLE genes (
    id VARCHAR(50) NOT NULL,
    model_id INT NOT NULL,
    PRIMARY KEY (id, model_id),
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Metabolites table: metabolic compounds
CREATE TABLE metabolites (
    id VARCHAR(50) NOT NULL,
    model_id INT NOT NULL,
    name TEXT,
    PRIMARY KEY (id, model_id),
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reaction-Gene relationships with GPR rules
CREATE TABLE reaction_genes (
    reaction_id VARCHAR(50) NOT NULL,
    gene_id VARCHAR(50) NOT NULL,
    model_id INT NOT NULL,
    gpr_rule TEXT,
    PRIMARY KEY (reaction_id, gene_id, model_id),
    FOREIGN KEY (reaction_id, model_id) REFERENCES reactions(id, model_id) ON DELETE CASCADE,
    FOREIGN KEY (gene_id, model_id) REFERENCES genes(id, model_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reaction-Metabolite relationships with stoichiometric coefficients
CREATE TABLE reaction_metabolites (
    reaction_id VARCHAR(50) NOT NULL,
    metabolite_id VARCHAR(50) NOT NULL,
    model_id INT NOT NULL,
    coefficient FLOAT NOT NULL,
    PRIMARY KEY (reaction_id, metabolite_id, model_id),
    FOREIGN KEY (reaction_id, model_id) REFERENCES reactions(id, model_id) ON DELETE CASCADE,
    FOREIGN KEY (metabolite_id, model_id) REFERENCES metabolites(id, model_id) ON DELETE CASCADE,
    KEY idx_coefficient (coefficient)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reaction annotations: database cross-references
CREATE TABLE reaction_annotations (
    reaction_id VARCHAR(50) NOT NULL,
    model_id INT NOT NULL,
    db_name VARCHAR(50) NOT NULL,
    db_value TEXT,
    PRIMARY KEY (reaction_id, model_id, db_name),
    FOREIGN KEY (reaction_id, model_id) REFERENCES reactions(id, model_id) ON DELETE CASCADE,
    KEY idx_db_name (db_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reaction notes: metadata and references
CREATE TABLE reaction_notes (
    reaction_id VARCHAR(50) NOT NULL,
    model_id INT NOT NULL,
    note_key VARCHAR(100) NOT NULL,
    note_value TEXT,
    PRIMARY KEY (reaction_id, model_id, note_key),
    FOREIGN KEY (reaction_id, model_id) REFERENCES reactions(id, model_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User selections: tracks which reactions users have selected
CREATE TABLE user_selections (
    session_id VARCHAR(255) NOT NULL,
    model_id INT NOT NULL,
    reaction_id VARCHAR(50) NOT NULL,
    selected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (session_id, reaction_id, model_id),
    FOREIGN KEY (reaction_id, model_id) REFERENCES reactions(id, model_id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE,
    KEY idx_session (session_id),
    KEY idx_selected_at (selected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FBA results: stores FBA analysis results with configuration
CREATE TABLE fba_results (
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

-- Initial model record for Human-GEM
INSERT INTO models (id, name, version, github_url) VALUES
(1, 'Human-GEM', 'v1.15.0', 'https://github.com/SysBioChalmers/Human-GEM');
