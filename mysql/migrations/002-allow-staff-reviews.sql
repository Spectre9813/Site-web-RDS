-- Migration: Allow both administrators and pilots to review companies
-- Date: 2026-06-03
-- Desc: The original `business_review` table only allowed pilots to post
--       reviews (FK pilot_id_business_review -> pilot.id_pilot). Administrators
--       are stored in the `administrator` table, not `pilot`, so they were
--       rejected by the foreign key. We re-point the FK to `user(id_user)` so
--       that any staff member (admin OR pilot) can rate a company. The column
--       name is kept for backward compatibility; it now means "reviewer".
--
-- This script is idempotent: it can be run multiple times safely.

-- ----------------------------------------------------------------------------
-- 1. Drop the old FK that points to `pilot` (constraint name is auto-generated,
--    so we look it up dynamically).
-- ----------------------------------------------------------------------------
SET @old_fk = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'business_review'
      AND COLUMN_NAME = 'pilot_id_business_review'
      AND REFERENCED_TABLE_NAME = 'pilot'
    LIMIT 1
);

SET @drop_sql = IF(
    @old_fk IS NULL,
    'SELECT "No pilot FK to drop" AS info',
    CONCAT('ALTER TABLE business_review DROP FOREIGN KEY ', @old_fk)
);

PREPARE stmt_drop FROM @drop_sql;
EXECUTE stmt_drop;
DEALLOCATE PREPARE stmt_drop;

-- ----------------------------------------------------------------------------
-- 2. Add the new FK pointing to `user(id_user)` (only if it does not exist yet).
-- ----------------------------------------------------------------------------
SET @new_fk = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'business_review'
      AND COLUMN_NAME = 'pilot_id_business_review'
      AND REFERENCED_TABLE_NAME = 'user'
    LIMIT 1
);

SET @add_sql = IF(
    @new_fk IS NULL,
    'ALTER TABLE business_review
        ADD CONSTRAINT fk_business_review_reviewer
        FOREIGN KEY (pilot_id_business_review)
        REFERENCES user(id_user) ON DELETE CASCADE',
    'SELECT "Reviewer FK already present" AS info'
);

PREPARE stmt_add FROM @add_sql;
EXECUTE stmt_add;
DEALLOCATE PREPARE stmt_add;
