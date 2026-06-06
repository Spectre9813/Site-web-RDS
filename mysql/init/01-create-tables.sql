-- ============================================================
--
--                   Notre base de donnée
--
-- ============================================================

-- Connection à la base de donnée
USE sql_db;

-- Suppression des tables dans le bon ordre
DROP TABLE IF EXISTS wishlist, business_review, application, offer_requirement, student_enrollment,
    promotion_assignment, internship_offer, skill, company_site, company, business_sector, promotion, campus,
    administrator, student, pilot, user;

-- ============================================================
-- USER MANAGEMENT
-- ============================================================

CREATE TABLE user (
    id_user INT NOT NULL AUTO_INCREMENT,
    email_user VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name_user VARCHAR(100),
    last_name_user VARCHAR(100),
    is_active_user BOOLEAN DEFAULT 1,
    created_at_user DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user)
) ENGINE=InnoDB;

CREATE TABLE pilot (
    id_pilot INT NOT NULL,
    PRIMARY KEY (id_pilot),
    FOREIGN KEY (id_pilot) REFERENCES user(id_user) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE student (
    id_student INT NOT NULL,
    status_student ENUM('searching', 'hired', 'inactive') DEFAULT 'searching',
    PRIMARY KEY (id_student),
    FOREIGN KEY (id_student) REFERENCES user(id_user) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE administrator (
    id_administrator INT NOT NULL,
    PRIMARY KEY (id_administrator),
    FOREIGN KEY (id_administrator) REFERENCES user(id_user) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ORGANIZATION
-- ============================================================

CREATE TABLE campus (
    id_campus INT NOT NULL AUTO_INCREMENT,
    name_campus VARCHAR(100) NOT NULL,
    address_campus TEXT,
    PRIMARY KEY (id_campus)
) ENGINE=InnoDB;

CREATE TABLE promotion (
    id_promotion INT NOT NULL AUTO_INCREMENT,
    label_promotion VARCHAR(100) NOT NULL,
    academic_year_promotion VARCHAR(20),
    campus_id_promotion INT NOT NULL,
    PRIMARY KEY (id_promotion),
    FOREIGN KEY (campus_id_promotion) REFERENCES campus(id_campus) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- BUSINESS & OFFERS
-- ============================================================

CREATE TABLE business_sector (
    id_business_sector INT NOT NULL AUTO_INCREMENT,
    name_business_sector VARCHAR(100) NOT NULL,
    description_business_sector TEXT,
    PRIMARY KEY (id_business_sector)
) ENGINE=InnoDB;

CREATE TABLE company (
    id_company INT NOT NULL AUTO_INCREMENT,
    name_company VARCHAR(255) NOT NULL,
    description_company TEXT,
    email_company VARCHAR(255),
    phone_company VARCHAR(20),
    tax_id_company VARCHAR(14), -- SIRET
    is_active_company BOOLEAN DEFAULT 1,
    sector_id_company INT NOT NULL,
    created_at_company DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_company),
    FOREIGN KEY (sector_id_company) REFERENCES business_sector(id_business_sector) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE company_site (
    id_company_site INT NOT NULL AUTO_INCREMENT,
    address_company_site TEXT NOT NULL,
    city_company_site VARCHAR(100),
    company_id_company_site INT NOT NULL,
    PRIMARY KEY (id_company_site),
    FOREIGN KEY (company_id_company_site) REFERENCES company(id_company) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE skill (
    id_skill INT NOT NULL AUTO_INCREMENT,
    label_skill VARCHAR(100) NOT NULL UNIQUE,
    PRIMARY KEY (id_skill)
) ENGINE=InnoDB;

CREATE TABLE internship_offer (
    id_internship_offer INT NOT NULL AUTO_INCREMENT,
    title_internship_offer VARCHAR(255) NOT NULL,
    description_internship_offer TEXT,
    hourly_rate_internship_offer DECIMAL(10, 2),
    start_date_internship_offer DATE,
    duration_weeks_internship_offer INT,
    is_active_internship_offer BOOLEAN DEFAULT 1,
    published_at_internship_offer DATETIME DEFAULT CURRENT_TIMESTAMP,
    company_site_id_internship_offer INT NOT NULL,
    PRIMARY KEY (id_internship_offer),
    FOREIGN KEY (company_site_id_internship_offer) REFERENCES company_site(id_company_site) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ASSOCIATIVE TABLES (Relationships)
-- ============================================================

-- Pilot managing a Promotion
CREATE TABLE promotion_assignment (
    promotion_assignment_id INT NOT NULL,
    pilot_assignment_id INT NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (promotion_assignment_id, pilot_assignment_id),
    FOREIGN KEY (promotion_assignment_id) REFERENCES promotion(id_promotion) ON DELETE CASCADE,
    FOREIGN KEY (pilot_assignment_id) REFERENCES pilot(id_pilot) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Student enrolled in a Promotion
CREATE TABLE student_enrollment (
    promotion_id_student_enrollment INT NOT NULL,
    student_id_student_enrollment INT NOT NULL,
    enrolled_at DATE,
    PRIMARY KEY (promotion_id_student_enrollment, student_id_student_enrollment),
    FOREIGN KEY (promotion_id_student_enrollment) REFERENCES promotion(id_promotion) ON DELETE CASCADE,
    FOREIGN KEY (student_id_student_enrollment) REFERENCES student(id_student) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Skills required for an Offer
CREATE TABLE offer_requirement (
    offer_requirement_id INT NOT NULL,
    skill_requirement_id INT NOT NULL,
    PRIMARY KEY (offer_requirement_id, skill_requirement_id),
    FOREIGN KEY (offer_requirement_id) REFERENCES internship_offer(id_internship_offer) ON DELETE CASCADE,
    FOREIGN KEY (skill_requirement_id) REFERENCES skill(id_skill) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Internship Application
CREATE TABLE application (
    id_application INT NOT NULL AUTO_INCREMENT,
    student_id_application INT NOT NULL,
    offer_id_application INT NOT NULL,
    cv_path_application VARCHAR(255),
    cover_letter_path_application VARCHAR(255),
    status_application ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    applied_at_application DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_application),
    FOREIGN KEY (student_id_application) REFERENCES student(id_student) ON DELETE CASCADE,
    FOREIGN KEY (offer_id_application) REFERENCES internship_offer(id_internship_offer) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Staff member (admin OR pilot) reviewing a Company.
-- NOTE: the column is named pilot_id_business_review for historical reasons,
-- but it now references user(id_user), so BOTH administrators and pilots can
-- post a review. Treat it semantically as "reviewer id".
CREATE TABLE business_review (
    pilot_id_business_review INT NOT NULL,
    company_id_business_review INT NOT NULL,
    rating_business_review TINYINT CHECK (rating_business_review BETWEEN 1 AND 5),
    comment_business_review TEXT,
    reviewed_at_business_review DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (pilot_id_business_review, company_id_business_review),
    CONSTRAINT fk_business_review_reviewer
        FOREIGN KEY (pilot_id_business_review) REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (company_id_business_review) REFERENCES company(id_company) ON DELETE CASCADE
) ENGINE=InnoDB; 

-- Student Wishlist (Favorites)
CREATE TABLE wishlist (
    student_id_wishlist INT NOT NULL,
    offer_id_wishlist INT NOT NULL,
    saved_at_wishlist DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id_wishlist, offer_id_wishlist),
    FOREIGN KEY (student_id_wishlist) REFERENCES student(id_student) ON DELETE CASCADE,
    FOREIGN KEY (offer_id_wishlist) REFERENCES internship_offer(id_internship_offer) ON DELETE CASCADE
) ENGINE=InnoDB;


ALTER TABLE internship_offer ADD COLUMN views_internship_offer INT UNSIGNED NOT NULL DEFAULT 0;
-- nécessaire pour le compteur de vue, sinon c'était une nouvelle table.
