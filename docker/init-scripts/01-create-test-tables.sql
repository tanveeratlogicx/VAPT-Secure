-- Create VAPT Secure test tables for development
-- This runs automatically when the database container starts

-- Ensure plugin tables exist (WordPress will create these, but good for reference)
-- The plugin creates these on activation

-- Feature Status Table
CREATE TABLE IF NOT EXISTS wp_vaptsecure_feature_status (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feature_key VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('draft', 'develop', 'test', 'release') DEFAULT 'draft',
    implemented_at DATETIME NULL,
    assigned_to BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_feature_key (feature_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature Metadata Table
CREATE TABLE IF NOT EXISTS wp_vaptsecure_feature_meta (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feature_key VARCHAR(255) NOT NULL UNIQUE,
    feature_label TEXT,
    risk_category VARCHAR(100),
    generated_schema LONGTEXT,
    original_user_need TEXT,
    is_enforced TINYINT(1) DEFAULT 0,
    is_enabled TINYINT(1) DEFAULT 1,
    is_strict_mode TINYINT(1) DEFAULT 0,
    implementation_data LONGTEXT,
    override_schema LONGTEXT,
    override_implementation_data LONGTEXT,
    is_adaptive_deployment TINYINT(1) DEFAULT 0,
    wp_config_snippet LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feature (feature_key),
    INDEX idx_enforced (is_enforced)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature History Table (Audit Log)
CREATE TABLE IF NOT EXISTS wp_vaptsecure_feature_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feature_key VARCHAR(255) NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    user_id BIGINT UNSIGNED NULL,
    note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feature (feature_key),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Domains Table
CREATE TABLE IF NOT EXISTS wp_vaptsecure_domains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE,
    is_primary TINYINT(1) DEFAULT 0,
    features JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain),
    INDEX idx_primary (is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature Statistics Table
CREATE TABLE IF NOT EXISTS wp_vaptsecure_feature_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feature_key VARCHAR(255) NOT NULL UNIQUE,
    execution_count BIGINT UNSIGNED DEFAULT 0,
    violations_blocked BIGINT UNSIGNED DEFAULT 0,
    last_triggered DATETIME NULL,
    avg_response_ms DECIMAL(10,2) DEFAULT 0,
    peak_response_ms INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feature (feature_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grant privileges
GRANT ALL PRIVILEGES ON vaptsecure_dev.* TO 'vaptsecure_user'@'localhost';
GRANT ALL PRIVILEGES ON vaptsecure_dev.* TO 'vaptsecure_user'@'%';
FLUSH PRIVILEGES;
