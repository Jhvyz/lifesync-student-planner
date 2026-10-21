ALTER TABLE users
    ADD COLUMN first_name VARCHAR(50) NULL AFTER full_name,
    ADD COLUMN last_name VARCHAR(50) NULL AFTER first_name,
    ADD COLUMN gender ENUM('Male', 'Female', 'Other', 'Prefer not to say') NULL AFTER last_name,
    ADD COLUMN address VARCHAR(255) NULL AFTER gender;
