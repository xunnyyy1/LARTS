-- ============================================================
-- LIVELIHOOD ASSISTANCE AND RESOURCE TRACKING SYSTEM (LARTS)
-- Database Schema + Seed Data
-- Davao Del Norte State College – IT223
-- ============================================================

CREATE DATABASE IF NOT EXISTS larts_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE larts_db;

-- ─── USERS ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','staff','encoder') NOT NULL DEFAULT 'encoder',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── HOUSEHOLDS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS households (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    family_name     VARCHAR(100) NOT NULL,
    head_name       VARCHAR(100) NOT NULL,
    barangay        VARCHAR(100) NOT NULL,
    address         TEXT NOT NULL,
    contact         VARCHAR(20),
    members_count   INT DEFAULT 1,
    monthly_income  DECIMAL(12,2) DEFAULT 0.00,
    monthly_expenses DECIMAL(12,2) DEFAULT 0.00,
    economic_status ENUM('stable','at_risk','vulnerable') DEFAULT 'stable',
    date_registered DATE NOT NULL,
    created_by      INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─── INCOME RECORDS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS income_records (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    household_id    INT NOT NULL,
    income_type     VARCHAR(100) NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    date_recorded   DATE NOT NULL,
    notes           TEXT,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── EXPENSE RECORDS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS expense_records (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    household_id        INT NOT NULL,
    expense_category    VARCHAR(100) NOT NULL,
    amount              DECIMAL(12,2) NOT NULL,
    date_recorded       DATE NOT NULL,
    notes               TEXT,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── ASSISTANCE RECORDS ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS assistance_records (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    household_id    INT NOT NULL,
    assistance_type VARCHAR(100) NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    date_released   DATE NOT NULL,
    remarks         TEXT,
    staff_id        INT,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─── BARANGAYS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS barangays (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── ASSISTANCE TYPES ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS assistance_types (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,
    description     TEXT,
    max_amount      DECIMAL(12,2) DEFAULT 0.00,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── HOUSEHOLD MEMBERS ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS household_members (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    household_id    INT NOT NULL,
    name            VARCHAR(100) NOT NULL,
    relationship    VARCHAR(50) NOT NULL,
    age             INT,
    occupation      VARCHAR(100),
    contact         VARCHAR(20),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── GRIEVANCES ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS grievances (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    household_id    INT NOT NULL,
    subject         VARCHAR(255) NOT NULL,
    description     TEXT NOT NULL,
    status          ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    priority        ENUM('low','medium','high','urgent') DEFAULT 'medium',
    filed_by        INT,
    resolved_by     INT,
    resolution_notes TEXT,
    date_filed      DATE NOT NULL,
    date_resolved   DATE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (filed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─── AUDIT LOGS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT,
    action          VARCHAR(50) NOT NULL,
    table_name      VARCHAR(100),
    record_id       INT,
    old_values      JSON,
    new_values      JSON,
    description     TEXT,
    ip_address      VARCHAR(45),
    user_agent      VARCHAR(255),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (created_at),
    INDEX (user_id),
    INDEX (action)
) ENGINE=InnoDB;

-- ─── DATABASE INDEXES ─────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_households_barangay ON households(barangay);
CREATE INDEX IF NOT EXISTS idx_households_family_name ON households(family_name);
CREATE INDEX IF NOT EXISTS idx_households_economic_status ON households(economic_status);
CREATE INDEX IF NOT EXISTS idx_income_records_household ON income_records(household_id);
CREATE INDEX IF NOT EXISTS idx_income_records_date ON income_records(date_recorded);
CREATE INDEX IF NOT EXISTS idx_expense_records_household ON expense_records(household_id);
CREATE INDEX IF NOT EXISTS idx_expense_records_date ON expense_records(date_recorded);
CREATE INDEX IF NOT EXISTS idx_assistance_records_household ON assistance_records(household_id);
CREATE INDEX IF NOT EXISTS idx_assistance_records_date ON assistance_records(date_released);
CREATE INDEX IF NOT EXISTS idx_household_members_household ON household_members(household_id);
CREATE INDEX IF NOT EXISTS idx_grievances_household ON grievances(household_id);
CREATE INDEX IF NOT EXISTS idx_grievances_status ON grievances(status);

-- ─── VIEWS FOR REPORTING ─────────────────────────────────────────
CREATE OR REPLACE VIEW vw_household_financial_status AS
SELECT h.id,
       h.family_name,
       h.head_name,
       h.barangay,
       h.monthly_income,
       h.monthly_expenses,
       h.monthly_income - h.monthly_expenses AS balance,
       h.economic_status,
       h.date_registered
FROM households h;

CREATE OR REPLACE VIEW vw_assistance_distribution AS
SELECT h.barangay,
       a.assistance_type,
       COUNT(*) AS total_distributions,
       SUM(a.amount) AS total_amount,
       MIN(a.date_released) AS first_release,
       MAX(a.date_released) AS last_release
FROM assistance_records a
JOIN households h ON a.household_id = h.id
GROUP BY h.barangay, a.assistance_type;

-- ─── TRIGGERS FOR DATA CONSISTENCY AND AUDIT ─────────────────────
DROP TRIGGER IF EXISTS trg_households_audit_insert;
DELIMITER $$
CREATE TRIGGER trg_households_audit_insert
AFTER INSERT ON households
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, description, created_at)
    VALUES (
        NEW.created_by,
        'CREATE',
        'households',
        NEW.id,
        JSON_OBJECT(
            'family_name', NEW.family_name,
            'head_name', NEW.head_name,
            'barangay', NEW.barangay,
            'members_count', NEW.members_count,
            'monthly_income', NEW.monthly_income,
            'monthly_expenses', NEW.monthly_expenses,
            'economic_status', NEW.economic_status
        ),
        CONCAT('Created household ', NEW.family_name),
        NOW()
    );
END$$
DROP TRIGGER IF EXISTS trg_households_audit_update$$
CREATE TRIGGER trg_households_audit_update
AFTER UPDATE ON households
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, description, created_at)
    VALUES (
        NEW.created_by,
        'UPDATE',
        'households',
        NEW.id,
        JSON_OBJECT(
            'family_name', OLD.family_name,
            'head_name', OLD.head_name,
            'barangay', OLD.barangay,
            'members_count', OLD.members_count,
            'monthly_income', OLD.monthly_income,
            'monthly_expenses', OLD.monthly_expenses,
            'economic_status', OLD.economic_status
        ),
        JSON_OBJECT(
            'family_name', NEW.family_name,
            'head_name', NEW.head_name,
            'barangay', NEW.barangay,
            'members_count', NEW.members_count,
            'monthly_income', NEW.monthly_income,
            'monthly_expenses', NEW.monthly_expenses,
            'economic_status', NEW.economic_status
        ),
        CONCAT('Updated household ', NEW.family_name),
        NOW()
    );
END$$
DROP TRIGGER IF EXISTS trg_assistance_amount_positive$$
CREATE TRIGGER trg_assistance_amount_positive
BEFORE INSERT ON assistance_records
FOR EACH ROW
BEGIN
    IF NEW.amount <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assistance amount must be greater than zero';
    END IF;
END$$
DELIMITER ;

-- ─── SEED DATA ────────────────────────────────────────────────
-- Passwords: Admin@123, Staff@123, Encoder@123 (bcrypt hashed)
INSERT INTO users (name, username, password, role) VALUES
('Admin User',       'admin',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Maria Santos',     'mstaff',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Jose Encoder',     'jencoder','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'encoder');
-- Default password for all: "password"

INSERT INTO households (family_name, head_name, barangay, address, contact, members_count, monthly_income, monthly_expenses, economic_status, date_registered, created_by) VALUES
('Reyes',    'Juan Reyes',     'Bgy. Sto. Tomas',  '123 Mango St.',   '09171234567', 5, 8500.00, 9200.00, 'vulnerable', '2024-01-10', 1),
('Dela Cruz','Ana Dela Cruz',  'Bgy. Kalikasan',   '456 Sampaguita',  '09281234567', 4, 12000.00,10500.00, 'stable',     '2024-01-15', 1),
('Bautista', 'Pedro Bautista', 'Bgy. Sto. Tomas',  '789 Narra Ave.',  '09391234567', 6, 9800.00, 9800.00,  'at_risk',    '2024-02-01', 2),
('Flores',   'Rosa Flores',    'Bgy. Communal',    '321 Acacia St.',  '09101234567', 3, 7000.00, 8500.00,  'vulnerable', '2024-02-14', 2),
('Gonzales', 'Mario Gonzales', 'Bgy. New Visayas', '654 Ipil Rd.',    '09201234567', 7, 15000.00,11000.00, 'stable',     '2024-03-05', 1),
('Fernandez','Cita Fernandez', 'Bgy. Kalikasan',   '987 Mahogany',    '09301234567', 2, 6500.00, 7200.00,  'vulnerable', '2024-03-12', 2),
('Villanueva','Roy Villanueva','Bgy. Communal',     '111 Cedar Lane',  '09401234567', 5, 11000.00,10000.00, 'stable',     '2024-04-01', 1),
('Cruz',     'Lita Cruz',      'Bgy. New Visayas', '222 Pine St.',    '09501234567', 4, 9500.00, 9500.00,  'at_risk',    '2024-04-22', 2);

INSERT INTO income_records (household_id, income_type, amount, date_recorded) VALUES
(1, 'Salary',              5000.00, '2024-05-01'),
(1, 'Small Business',      3500.00, '2024-05-01'),
(2, 'Salary',             10000.00, '2024-05-01'),
(2, 'Remittance',          2000.00, '2024-05-01'),
(3, 'Livelihood Assistance',4800.00,'2024-05-01'),
(3, 'Small Business',      5000.00, '2024-05-01'),
(4, 'Salary',              7000.00, '2024-05-01'),
(5, 'Salary',             12000.00, '2024-05-01'),
(5, 'Small Business',      3000.00, '2024-05-01');

INSERT INTO expense_records (household_id, expense_category, amount, date_recorded) VALUES
(1, 'Food',           4000.00, '2024-05-01'),
(1, 'Utilities',      1500.00, '2024-05-01'),
(1, 'Transportation', 1200.00, '2024-05-01'),
(1, 'Education',      1500.00, '2024-05-01'),
(1, 'Medical',        1000.00, '2024-05-01'),
(2, 'Food',           4500.00, '2024-05-01'),
(2, 'Utilities',      2000.00, '2024-05-01'),
(2, 'Education',      2000.00, '2024-05-01'),
(2, 'Transportation', 2000.00, '2024-05-01'),
(4, 'Food',           3500.00, '2024-05-01'),
(4, 'Utilities',      2000.00, '2024-05-01'),
(4, 'Transportation', 1500.00, '2024-05-01'),
(4, 'Medical',        1500.00, '2024-05-01');

INSERT INTO assistance_records (household_id, assistance_type, amount, date_released, remarks, staff_id) VALUES
(1, '4Ps Cash Grant',        1400.00, '2024-04-15', 'Q1 release',            2),
(4, '4Ps Cash Grant',        1400.00, '2024-04-15', 'Q1 release',            2),
(6, 'Food Pack',              500.00, '2024-04-20', 'Monthly food assistance',2),
(1, 'Livelihood Starter Kit',2500.00, '2024-03-10', 'Sari-sari store kit',   1),
(3, '4Ps Cash Grant',        1400.00, '2024-04-15', 'Q1 release',            2),
(2, 'Educational Assistance',3000.00, '2024-05-02', 'School supplies',       2);

-- ─── BARANGAY SEED DATA ────────────────────────────────────────
INSERT INTO barangays (name, description) VALUES
('Bgy. Sto. Tomas', 'Downtown barangay with high population density'),
('Bgy. Kalikasan', 'Rural barangay focused on agriculture'),
('Bgy. Communal', 'Community-oriented barangay'),
('Bgy. New Visayas', 'Newly developed residential area');

-- ─── ASSISTANCE TYPES SEED DATA ────────────────────────────────
INSERT INTO assistance_types (name, description, max_amount) VALUES
('4Ps Cash Grant', 'Pantawid Pamilyang Pilipino Program assistance', 1400.00),
('Food Pack', 'Emergency food assistance', 500.00),
('Livelihood Starter Kit', 'Business starter kits for self-employment', 5000.00),
('Educational Assistance', 'School supplies and educational materials', 3000.00),
('Medical Assistance', 'Healthcare and medical expenses support', 10000.00),
('Housing Assistance', 'Emergency shelter and housing repairs', 15000.00);

-- ─── HOUSEHOLD MEMBERS SEED DATA ───────────────────────────────
INSERT INTO household_members (household_id, name, relationship, age, occupation, contact) VALUES
(1, 'Juan Reyes', 'Head', 45, 'Driver', '09171234567'),
(1, 'Maria Reyes', 'Spouse', 42, 'Housewife', NULL),
(1, 'Jose Reyes', 'Son', 18, 'Student', NULL),
(2, 'Ana Dela Cruz', 'Head', 50, 'Teacher', '09281234567'),
(2, 'Carlos Dela Cruz', 'Son', 22, 'Office Worker', NULL),
(3, 'Pedro Bautista', 'Head', 55, 'Farmer', '09391234567'),
(3, 'Rosa Bautista', 'Spouse', 52, 'Farmer', NULL),
(4, 'Rosa Flores', 'Head', 38, 'Vendor', '09101234567');

-- ─── GRIEVANCES SEED DATA ──────────────────────────────────────
INSERT INTO grievances (household_id, subject, description, status, priority, filed_by, date_filed) VALUES
(1, 'Delayed Assistance Release', '4Ps payment delayed for 2 weeks', 'resolved', 'high', 1, '2024-04-10'),
(3, 'Assistance Eligibility Query', 'Question regarding household eligibility for programs', 'open', 'medium', 2, '2024-05-01'),
(5, 'Housing Concern', 'Need assistance with roof repairs', 'in_progress', 'high', 1, '2024-04-25');