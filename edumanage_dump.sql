-- PostgreSQL-compatible schema and data for edumanage
-- Converted from MySQL dump for Render PostgreSQL

-- Drop tables if they exist
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create students table
CREATE TABLE students (
    student_id SERIAL PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    date_of_birth DATE NOT NULL,
    enrollment_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Insert data into users
INSERT INTO users (user_id, username, password, created_at) VALUES
(1, 'JD', '$2y$10$jXBS85lNEHWmzfEuFchj3ei6iJrTCRSg9qHAYp/ZLa23OJRkKxNY6', '2025-08-27 17:44:45'),
(2, 'kennedy', '$2y$10$yaqbE6j3KMiIrEKLOpkRZO7GmLFuh.UYlAWVG3TM12ABX963IKTm.', '2025-08-27 17:46:38');

-- Insert data into students
INSERT INTO students (student_id, first_name, last_name, email, date_of_birth, enrollment_date, created_at) VALUES
(1, 'Frank', 'Nsenga', 'frank.nsenga@gmail.com', '2025-08-30', '2025-08-30', '2025-08-27 17:45:44');