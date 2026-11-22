-- Store the notifications shown in the navigation menu and notification page.
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(40) NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    reference_key VARCHAR(120) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_reference (user_id, reference_key),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Store each time a patient reports taking or skipping a medicine.
CREATE TABLE IF NOT EXISTS medication_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    report_drug_id INT NOT NULL,
    patient_id INT NOT NULL,
    taken_date DATE NOT NULL,
    taken_time TIME DEFAULT NULL,
    status ENUM('taken','skipped') NOT NULL DEFAULT 'taken',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_medication_prescription (report_drug_id),
    KEY idx_medication_patient (patient_id),
    CONSTRAINT fk_log_prescription FOREIGN KEY (report_drug_id) REFERENCES report_drugs(report_drug_id) ON DELETE CASCADE,
    CONSTRAINT fk_log_patient FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Store temporary links used by the password reset pages.
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_password_reset_hash (token_hash),
    KEY idx_password_reset_user (user_id),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Store personal medical diary entries for each patient.
CREATE TABLE IF NOT EXISTS diary_entries (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    entry_date DATE NOT NULL,
    title VARCHAR(120) NOT NULL,
    notes TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_diary_patient FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Store simple settings that administrators can change.
CREATE TABLE IF NOT EXISTS system_settings (
    setting_name VARCHAR(80) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Add the default settings only when they do not exist yet.
INSERT IGNORE INTO system_settings (
    setting_name,
    setting_value
)
VALUES
    ('site_name', 'HealthGuard'),
    ('appointment_reminder', '1 day before');

-- Add a phone column to older users tables when it is missing.
SET @add_phone = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE()
        AND TABLE_NAME='users'
        AND COLUMN_NAME='phone'
    )=0,
    'ALTER TABLE users ADD phone VARCHAR(30) DEFAULT NULL',
    'SELECT 1'
);
PREPARE add_phone_statement FROM @add_phone;
EXECUTE add_phone_statement;
DEALLOCATE PREPARE add_phone_statement;

-- Add a national identity column to older users tables when it is missing.
SET @add_national_id = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE()
        AND TABLE_NAME='users'
        AND COLUMN_NAME='national_id'
    )=0,
    'ALTER TABLE users ADD national_id VARCHAR(20) DEFAULT NULL',
    'SELECT 1'
);
PREPARE add_id_statement FROM @add_national_id;
EXECUTE add_id_statement;
DEALLOCATE PREPARE add_id_statement;

-- Add a city column to older users tables when it is missing.
SET @add_city = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE()
        AND TABLE_NAME='users'
        AND COLUMN_NAME='city'
    )=0,
    'ALTER TABLE users ADD city VARCHAR(80) DEFAULT NULL',
    'SELECT 1'
);
PREPARE add_city_statement FROM @add_city;
EXECUTE add_city_statement;
DEALLOCATE PREPARE add_city_statement;

-- Older versions allowed only one medicine log per day. Remove that limit.
SET @add_medication_index = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA=DATABASE()
        AND TABLE_NAME='medication_logs'
        AND INDEX_NAME='idx_medication_prescription'
    )=0,
    'ALTER TABLE medication_logs ADD INDEX idx_medication_prescription (report_drug_id)',
    'SELECT 1'
);
PREPARE add_medication_statement FROM @add_medication_index;
EXECUTE add_medication_statement;
DEALLOCATE PREPARE add_medication_statement;

-- Remove the old unique index when an older database still has it.
SET @drop_daily_limit = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA=DATABASE()
        AND TABLE_NAME='medication_logs'
        AND INDEX_NAME='unique_daily_medicine'
    )>0,
    'ALTER TABLE medication_logs DROP INDEX unique_daily_medicine',
    'SELECT 1'
);
PREPARE drop_daily_statement FROM @drop_daily_limit;
EXECUTE drop_daily_statement;
DEALLOCATE PREPARE drop_daily_statement;
