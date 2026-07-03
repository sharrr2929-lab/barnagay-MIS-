-- =====================================================================
-- Barangay Management Information System (BMIS)
-- Database Schema + Seed Data
-- Target: MySQL / MariaDB via XAMPP 8.2.12-0-VS16 (phpMyAdmin)
-- =====================================================================
-- HOW TO IMPORT:
--   1. Start Apache + MySQL in the XAMPP Control Panel.
--   2. Open http://localhost/phpmyadmin
--   3. Click "Import" -> choose this file -> Go.
--   (This file also creates the database, so you do NOT need to
--    create "barangay_mis" manually first.)
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS barangay_mis;
CREATE DATABASE barangay_mis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barangay_mis;

-- ---------------------------------------------------------------------
-- USERS  (system accounts / roles)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    role          ENUM('super_admin','captain','secretary','staff','tanod') NOT NULL DEFAULT 'staff',
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PUROKS / ZONES
-- ---------------------------------------------------------------------
CREATE TABLE puroks (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- HOUSEHOLDS
-- ---------------------------------------------------------------------
CREATE TABLE households (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    household_number    VARCHAR(50) NOT NULL UNIQUE,
    head_resident_id    INT NULL,
    purok_id            INT NULL,
    address             VARCHAR(255),
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purok_id) REFERENCES puroks(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RESIDENTS
-- ---------------------------------------------------------------------
CREATE TABLE residents (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    household_id      INT NULL,
    first_name        VARCHAR(100) NOT NULL,
    middle_name       VARCHAR(100),
    last_name         VARCHAR(100) NOT NULL,
    suffix            VARCHAR(20),
    birthdate         DATE NOT NULL,
    sex               ENUM('Male','Female') NOT NULL,
    civil_status      ENUM('Single','Married','Widowed','Separated','Divorced') NOT NULL DEFAULT 'Single',
    purok_id          INT NULL,
    address            VARCHAR(255),
    contact_number    VARCHAR(20),
    occupation        VARCHAR(100),
    is_voter          TINYINT(1) NOT NULL DEFAULT 0,
    is_pwd            TINYINT(1) NOT NULL DEFAULT 0,
    is_senior         TINYINT(1) NOT NULL DEFAULT 0,
    is_4ps            TINYINT(1) NOT NULL DEFAULT 0,
    photo             VARCHAR(255),
    status            ENUM('active','deceased','moved_out') NOT NULL DEFAULT 'active',
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE SET NULL,
    FOREIGN KEY (purok_id) REFERENCES puroks(id) ON DELETE SET NULL,
    INDEX idx_residents_last_name (last_name),
    INDEX idx_residents_status (status)
) ENGINE=InnoDB;

ALTER TABLE households
    ADD CONSTRAINT fk_household_head
    FOREIGN KEY (head_resident_id) REFERENCES residents(id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- OFFICIALS  (elected / appointed officials directory)
-- ---------------------------------------------------------------------
CREATE TABLE officials (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(150) NOT NULL,
    position      VARCHAR(100) NOT NULL,
    term_start    DATE,
    term_end      DATE,
    contact       VARCHAR(50),
    photo         VARCHAR(255),
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DOCUMENTS ISSUED  (certificates / clearances log)
-- ---------------------------------------------------------------------
CREATE TABLE documents_issued (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    resident_id      INT NOT NULL,
    document_type    VARCHAR(100) NOT NULL,
    purpose          VARCHAR(255),
    or_number        VARCHAR(50),
    amount           DECIMAL(10,2) NOT NULL DEFAULT 0,
    issued_by        INT NULL,
    issued_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_docs_type (document_type)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- BLOTTER REPORTS
-- ---------------------------------------------------------------------
CREATE TABLE blotter_reports (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    case_number      VARCHAR(50) UNIQUE,
    complainant      VARCHAR(150) NOT NULL,
    respondent       VARCHAR(150),
    incident_type    VARCHAR(100),
    incident_date    DATETIME,
    location         VARCHAR(255),
    narrative        TEXT,
    status           ENUM('Open','Under Mediation','Settled','Endorsed to Police/Court') NOT NULL DEFAULT 'Open',
    filed_by         INT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (filed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_blotter_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TANOD / ON-DUTY RESPONDER ROSTER
-- ---------------------------------------------------------------------
CREATE TABLE tanod_roster (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    full_name         VARCHAR(150) NOT NULL,
    contact_number    VARCHAR(20),
    role              VARCHAR(100) NOT NULL DEFAULT 'Tanod',
    shift_schedule    VARCHAR(100),
    status            ENUM('Available','On Call','Off Duty') NOT NULL DEFAULT 'Available',
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DISPATCH CALLS  (emergency / incident response)
-- ---------------------------------------------------------------------
CREATE TABLE dispatch_calls (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    caller_name         VARCHAR(150) NOT NULL,
    caller_contact      VARCHAR(20),
    incident_type       VARCHAR(100) NOT NULL,
    location            VARCHAR(255) NOT NULL,
    description         TEXT,
    priority            ENUM('Low','Medium','High','Emergency') NOT NULL DEFAULT 'Medium',
    status              ENUM('Pending','Dispatched','En Route','On Scene','Resolved','Closed') NOT NULL DEFAULT 'Pending',
    responder_id        INT NULL,
    called_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dispatched_at       DATETIME NULL,
    arrived_at          DATETIME NULL,
    resolved_at         DATETIME NULL,
    linked_blotter_id   INT NULL,
    created_by          INT NULL,
    FOREIGN KEY (responder_id) REFERENCES tanod_roster(id) ON DELETE SET NULL,
    FOREIGN KEY (linked_blotter_id) REFERENCES blotter_reports(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dispatch_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- BUSINESSES  (permits)
-- ---------------------------------------------------------------------
CREATE TABLE businesses (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    owner_resident_id    INT NULL,
    business_name        VARCHAR(150) NOT NULL,
    business_type        VARCHAR(100),
    address              VARCHAR(255),
    permit_number        VARCHAR(50) UNIQUE,
    date_issued          DATE,
    date_expiry          DATE,
    status               ENUM('Active','Expired','Revoked') NOT NULL DEFAULT 'Active',
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_resident_id) REFERENCES residents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ANNOUNCEMENTS / BULLETIN BOARD
-- ---------------------------------------------------------------------
CREATE TABLE announcements (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(200) NOT NULL,
    content       TEXT,
    image         VARCHAR(255),
    category      ENUM('Event','Advisory','Health','Emergency','General') NOT NULL DEFAULT 'General',
    posted_by     INT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EVENTS  (community events / calendar)
-- ---------------------------------------------------------------------
CREATE TABLE events (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    title             VARCHAR(200) NOT NULL,
    description       TEXT,
    event_type        VARCHAR(100),
    venue             VARCHAR(255),
    start_datetime    DATETIME NOT NULL,
    end_datetime      DATETIME NULL,
    organizer         VARCHAR(150),
    budget            DECIMAL(12,2) NOT NULL DEFAULT 0,
    status            ENUM('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
    created_by        INT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_events_start (start_datetime)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EVENT ATTENDEES
-- ---------------------------------------------------------------------
CREATE TABLE event_attendees (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    event_id       INT NOT NULL,
    resident_id    INT NULL,
    walkin_name    VARCHAR(150) NULL,
    is_present     TINYINT(1) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- REQUESTS / COMPLAINTS  (helpdesk-style tracking)
-- ---------------------------------------------------------------------
CREATE TABLE requests (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    resident_id       INT NULL,
    requestor_name    VARCHAR(150) NOT NULL,
    request_type      VARCHAR(100) NOT NULL,
    details           TEXT,
    status            ENUM('Pending','Processing','Released','Resolved') NOT NULL DEFAULT 'Pending',
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- AUDIT LOGS
-- ---------------------------------------------------------------------
CREATE TABLE audit_logs (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NULL,
    action            VARCHAR(100) NOT NULL,
    table_affected    VARCHAR(50),
    record_id         INT NULL,
    details           VARCHAR(255),
    timestamp         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SETTINGS  (key/value barangay profile + configuration)
-- ---------------------------------------------------------------------
CREATE TABLE settings (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    setting_key      VARCHAR(100) NOT NULL UNIQUE,
    setting_value    TEXT
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Default login: username "admin"  password "admin123"
-- (hash below is bcrypt for "admin123" — change this password after first login)
INSERT INTO users (username, password, full_name, role, status) VALUES
('admin',     '$2y$10$aDt5RK227abMETUOlGFYpu88htpjKYMIimZWepaZh1dAZdPIxXrhe', 'System Administrator', 'super_admin', 'active'),
('secretary', '$2y$10$aDt5RK227abMETUOlGFYpu88htpjKYMIimZWepaZh1dAZdPIxXrhe', 'Ana Reyes',            'secretary',   'active'),
('tanod1',    '$2y$10$aDt5RK227abMETUOlGFYpu88htpjKYMIimZWepaZh1dAZdPIxXrhe', 'Ramon Cruz',           'tanod',       'active');
-- NOTE: all three seeded accounts use the password "admin123".

INSERT INTO puroks (name) VALUES
('Purok 1 - Maligaya'), ('Purok 2 - Masagana'), ('Purok 3 - Magiting'),
('Purok 4 - Malaya'), ('Purok 5 - Mabuhay');

INSERT INTO officials (full_name, position, term_start, term_end, contact) VALUES
('Hon. Roberto Santos',   'Punong Barangay (Captain)', '2023-11-01', '2026-10-31', '0917-100-2001'),
('Hon. Liza Fernandez',   'Kagawad - Committee on Health', '2023-11-01', '2026-10-31', '0917-100-2002'),
('Hon. Mark Villanueva',  'Kagawad - Committee on Peace and Order', '2023-11-01', '2026-10-31', '0917-100-2003'),
('Hon. Grace Mendoza',    'Kagawad - Committee on Education', '2023-11-01', '2026-10-31', '0917-100-2004'),
('Hon. Paolo Aquino',     'SK Chairman', '2023-11-01', '2026-10-31', '0917-100-2005'),
('Ana Reyes',             'Barangay Secretary', '2023-11-01', '2026-10-31', '0917-100-2006'),
('Jun Dela Peña',         'Barangay Treasurer', '2023-11-01', '2026-10-31', '0917-100-2007');

INSERT INTO households (household_number, purok_id, address) VALUES
('HH-0001', 1, '12 Ilang-Ilang St., Purok 1'),
('HH-0002', 1, '45 Sampaguita St., Purok 1'),
('HH-0003', 2, '8 Narra St., Purok 2'),
('HH-0004', 3, '21 Mahogany St., Purok 3'),
('HH-0005', 4, '3 Kalachuchi St., Purok 4');

INSERT INTO residents (household_id, first_name, middle_name, last_name, birthdate, sex, civil_status, purok_id, address, contact_number, occupation, is_voter, is_pwd, is_senior, is_4ps, status) VALUES
(1, 'Juan',    'Santos',  'Dela Cruz',  '1978-05-14', 'Male',   'Married', 1, '12 Ilang-Ilang St., Purok 1', '0918-111-0001', 'Driver',            1, 0, 0, 0, 'active'),
(1, 'Maria',   'Lopez',   'Dela Cruz',  '1980-09-02', 'Female', 'Married', 1, '12 Ilang-Ilang St., Purok 1', '0918-111-0002', 'Vendor',            1, 0, 0, 1, 'active'),
(1, 'Josefa',  'Santos',  'Dela Cruz',  '1950-03-21', 'Female', 'Widowed', 1, '12 Ilang-Ilang St., Purok 1', '0918-111-0003', 'Retired',           1, 0, 1, 0, 'active'),
(2, 'Pedro',   'Ramos',   'Garcia',     '1990-11-30', 'Male',   'Single',  1, '45 Sampaguita St., Purok 1',  '0918-111-0004', 'Carpenter',         1, 0, 0, 0, 'active'),
(3, 'Elena',   'Cruz',    'Bautista',   '1985-02-17', 'Female', 'Married', 2, '8 Narra St., Purok 2',        '0918-111-0005', 'Teacher',           1, 0, 0, 0, 'active'),
(3, 'Ricardo', 'Torres',  'Bautista',   '1982-07-08', 'Male',   'Married', 2, '8 Narra St., Purok 2',        '0918-111-0006', 'Tricycle Driver',   1, 0, 0, 0, 'active'),
(4, 'Corazon', 'Reyes',   'Mendoza',    '1965-12-25', 'Female', 'Widowed', 3, '21 Mahogany St., Purok 3',    '0918-111-0007', 'Sari-sari Store',   1, 0, 1, 0, 'active'),
(4, 'Danilo',  'Aquino',  'Mendoza',    '2001-06-19', 'Male',   'Single',  3, '21 Mahogany St., Purok 3',    '0918-111-0008', 'Student',           0, 0, 0, 0, 'active'),
(5, 'Rosario', 'Villar',  'Navarro',    '1995-04-04', 'Female', 'Single',  4, '3 Kalachuchi St., Purok 4',   '0918-111-0009', 'Call Center Agent', 1, 1, 0, 0, 'active'),
(5, 'Antonio', 'Perez',   'Navarro',    '1958-08-13', 'Male',   'Married', 4, '3 Kalachuchi St., Purok 4',   '0918-111-0010', 'Security Guard',    1, 0, 1, 0, 'active');

UPDATE households SET head_resident_id = 1 WHERE id = 1;
UPDATE households SET head_resident_id = 4 WHERE id = 2;
UPDATE households SET head_resident_id = 6 WHERE id = 3;
UPDATE households SET head_resident_id = 7 WHERE id = 4;
UPDATE households SET head_resident_id = 10 WHERE id = 5;

INSERT INTO tanod_roster (full_name, contact_number, role, shift_schedule, status) VALUES
('Ramon Cruz',     '0917-200-3001', 'Tanod Chief', 'Mon-Fri 6AM-2PM', 'Available'),
('Willy Ocampo',   '0917-200-3002', 'Tanod',       'Mon-Fri 2PM-10PM', 'On Call'),
('Bert Manalo',    '0917-200-3003', 'Tanod',       'Sat-Sun 6AM-6PM',  'Off Duty'),
('Nora Salazar',   '0917-200-3004', 'Barangay Health Worker', 'Mon-Fri 8AM-5PM', 'Available');

INSERT INTO blotter_reports (case_number, complainant, respondent, incident_type, incident_date, location, narrative, status, filed_by) VALUES
('BLT-2026-001', 'Pedro Garcia', 'Unknown', 'Noise Complaint', '2026-06-20 22:15:00', 'Purok 1, Sampaguita St.', 'Complainant reported loud videoke noise past curfew hours.', 'Settled', 2),
('BLT-2026-002', 'Elena Bautista', 'Ricardo Bautista', 'Domestic Dispute', '2026-06-25 19:00:00', 'Purok 2, Narra St.', 'Verbal altercation between spouses, referred to Lupon for mediation.', 'Under Mediation', 2);

INSERT INTO dispatch_calls (caller_name, caller_contact, incident_type, location, description, priority, status, responder_id, dispatched_at, created_by) VALUES
('Corazon Mendoza', '0918-111-0007', 'Medical', 'Purok 3, Mahogany St.', 'Elderly resident feeling dizzy, requesting assistance to health center.', 'High', 'Resolved', 1, '2026-06-28 14:05:00', 2),
('Danilo Mendoza',  '0918-111-0008', 'Peace and Order', 'Purok 3 basketball court', 'Minor altercation between two groups of teenagers.', 'Medium', 'Pending', NULL, NULL, 2);

INSERT INTO businesses (owner_resident_id, business_name, business_type, address, permit_number, date_issued, date_expiry, status) VALUES
(7, 'Mendoza Sari-Sari Store', 'Retail', '21 Mahogany St., Purok 3', 'BP-2026-0001', '2026-01-05', '2027-01-05', 'Active'),
(2, 'Dela Cruz Karinderya', 'Food Service', '12 Ilang-Ilang St., Purok 1', 'BP-2025-0088', '2025-02-10', '2026-02-10', 'Expired');

INSERT INTO announcements (title, content, category, posted_by) VALUES
('Free Anti-Rabies Vaccination for Pets', 'The Barangay Health Center will conduct a free anti-rabies vaccination drive for dogs and cats. Please bring your pets to the covered court from 8AM to 3PM.', 'Health', 2),
('Schedule of Garbage Collection', 'Garbage collection will be every Monday, Wednesday, and Friday starting 6AM. Please segregate biodegradable and non-biodegradable waste.', 'Advisory', 2),
('Barangay Assembly Notice', 'All household heads are invited to the Quarterly Barangay Assembly. Attendance is highly encouraged for updates on community projects.', 'General', 1);

INSERT INTO events (title, description, event_type, venue, start_datetime, end_datetime, organizer, budget, status, created_by) VALUES
('Anti-Rabies Vaccination Drive', 'Free vaccination for pets in the barangay.', 'Vaccination/Medical Mission', 'Barangay Covered Court', '2026-07-10 08:00:00', '2026-07-10 15:00:00', 'Barangay Health Center', 5000.00, 'Upcoming', 2),
('Barangay Fiesta 2026', 'Annual barangay fiesta celebration with programs and activities.', 'Fiesta', 'Barangay Plaza', '2026-08-15 08:00:00', '2026-08-15 22:00:00', 'Office of the Punong Barangay', 150000.00, 'Upcoming', 1),
('Quarterly Barangay Assembly', 'Mandatory quarterly assembly for all household heads.', 'General Assembly', 'Barangay Hall Session Room', '2026-07-05 13:00:00', '2026-07-05 17:00:00', 'Barangay Secretary', 3000.00, 'Upcoming', 2);

INSERT INTO requests (resident_id, requestor_name, request_type, details, status) VALUES
(9, 'Rosario Navarro', 'Barangay Clearance', 'For job application requirements.', 'Pending'),
(4, 'Pedro Garcia', 'Certificate of Indigency', 'For PhilHealth assistance application.', 'Processing');

INSERT INTO settings (setting_key, setting_value) VALUES
('barangay_name', 'Barangay Malaya'),
('barangay_address', 'Purok 1, Malaya, City of San Fernando'),
('barangay_contact', '(02) 8123-4567'),
('barangay_email', 'barangaymalaya@example.gov.ph'),
('captain_name', 'Hon. Roberto Santos'),
('secretary_name', 'Ana Reyes'),
('logo_path', ''),
('clearance_fee', '50.00'),
('residency_fee', '30.00'),
('indigency_fee', '0.00'),
('business_clearance_fee', '200.00'),
('good_moral_fee', '30.00');
