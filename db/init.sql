-- CloudInsure schema — auto-insurance claims & dealer operations back-office.
-- Users + rich domain data are seeded idempotently by the PHP seeder
-- (so passwords are bcrypt-hashed and data volume is realistic).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(190) NOT NULL,
    email               VARCHAR(190) NOT NULL UNIQUE,
    password            VARCHAR(255) NOT NULL,
    role                ENUM('user','admin') NOT NULL DEFAULT 'user',
    department          VARCHAR(120) NOT NULL DEFAULT 'Claims',
    job_title           VARCHAR(120) NOT NULL DEFAULT 'Adjuster',
    phone               VARCHAR(60)  NULL,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    dealer_account_type INT NOT NULL DEFAULT 1,
    current_session_id  VARCHAR(190) NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Policyholders
CREATE TABLE IF NOT EXISTS customers (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    policy_number  VARCHAR(40) NOT NULL,
    name           VARCHAR(190) NOT NULL,
    email          VARCHAR(190) NOT NULL,
    phone          VARCHAR(60)  NULL,
    vehicle        VARCHAR(190) NULL,
    premium        DECIMAL(10,2) NOT NULL DEFAULT 0,
    policy_status  ENUM('active','pending','lapsed','cancelled') NOT NULL DEFAULT 'active',
    agent_id       INT NULL,
    start_date     DATE NULL,
    end_date       DATE NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dealer / agent partners. user_id intentionally NOT a foreign key so the
-- /user/0/dealer-account broken-access-control case can store orphan rows.
CREATE TABLE IF NOT EXISTS dealer_accounts (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    dealer_code         VARCHAR(40) NULL,
    name                VARCHAR(190) NOT NULL,
    email               VARCHAR(190) NOT NULL,
    phone               VARCHAR(60) NULL,
    region              VARCHAR(120) NULL,
    tier                ENUM('bronze','silver','gold','platinum') NOT NULL DEFAULT 'bronze',
    is_representative   TINYINT(1) NOT NULL DEFAULT 0,
    dealer_account_type INT NOT NULL DEFAULT 1,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subrogation / recovery claims — the core workflow.
CREATE TABLE IF NOT EXISTS subrogation_cases (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    case_number     VARCHAR(40) NOT NULL,
    customer_id     INT NULL,
    dealer_id       INT NULL,
    adjuster_id     INT NULL,
    incident_date   DATE NULL,
    claim_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    recovered_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status          ENUM('open','investigating','recovering','recovered','closed','denied') NOT NULL DEFAULT 'open',
    priority        ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    description     TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Free-text activity log on a case (timeline).
CREATE TABLE IF NOT EXISTS case_notes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    author_id   INT NULL,
    body        TEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    title              VARCHAR(255) NOT NULL,
    category_id        INT NOT NULL DEFAULT 1,
    body               TEXT NOT NULL,
    link_url           VARCHAR(500) NULL,
    type_id            INT NOT NULL DEFAULT 1,
    publish_start_date DATE NULL,
    publish_end_date   DATE NULL,
    created_by         INT NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contract_uploads (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(190) NULL,
    customer_id   INT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name   VARCHAR(255) NOT NULL,
    mime          VARCHAR(190) NOT NULL,
    size          INT NOT NULL DEFAULT 0,
    uploaded_by   INT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(190) NOT NULL,
    token      VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (id, name) VALUES
    (1, 'General'), (2, 'Claims'), (3, 'Policy Updates'), (4, 'System')
ON DUPLICATE KEY UPDATE name = VALUES(name);
