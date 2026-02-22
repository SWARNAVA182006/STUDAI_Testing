-- StudAI Career Platform - Database Setup Script
-- Run this script to create both databases

-- Create main database
CREATE DATABASE IF NOT EXISTS studai_career
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Create analytics database
CREATE DATABASE IF NOT EXISTS studai_career_analytics
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Create user with proper privileges (optional - adjust password)
-- CREATE USER IF NOT EXISTS 'studai_user'@'localhost' IDENTIFIED BY 'SecurePassword123!';
-- GRANT ALL PRIVILEGES ON studai_career.* TO 'studai_user'@'localhost';
-- GRANT ALL PRIVILEGES ON studai_career_analytics.* TO 'studai_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Verify databases created
SHOW DATABASES LIKE 'studai_career%';

-- Show database info
SELECT 
    SCHEMA_NAME as 'Database',
    DEFAULT_CHARACTER_SET_NAME as 'Charset',
    DEFAULT_COLLATION_NAME as 'Collation'
FROM information_schema.SCHEMATA
WHERE SCHEMA_NAME IN ('studai_career', 'studai_career_analytics');
