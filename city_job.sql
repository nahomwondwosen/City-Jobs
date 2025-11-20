-- Create database
CREATE DATABASE IF NOT EXISTS city_jobs 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_general_ci;

USE city_jobs;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  contact VARCHAR(150) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('freelancer','employer','admin') DEFAULT 'freelancer',
  profile_pic VARCHAR(255) DEFAULT 'default.png',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Jobs table (for future posting system)
CREATE TABLE jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employer_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  category VARCHAR(100),
  location VARCHAR(100),
  budget DECIMAL(10,2),
  status ENUM('open','closed') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Applications table (freelancers apply to jobs)
CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  freelancer_id INT NOT NULL,
  cover_letter TEXT,
  status ENUM('pending','accepted','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messages table (basic chat between users)
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT,
    file VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);


-- Ratings table (for freelancers/employers)
CREATE TABLE ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rated_user_id INT NOT NULL,
  rater_user_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rated_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (rater_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,              -- ID of the user (freelancer, employer, or admin)
    message VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table for available payment methods
CREATE TABLE payment_methods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  provider ENUM('chapa','telebirr') NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample methods
INSERT INTO payment_methods (name, provider, status) VALUES
('Chapa', 'chapa', 'active'),
('Telebirr', 'telebirr', 'active');

-- Table to store payments
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  status ENUM('pending','paid','failed') DEFAULT 'pending',
  reference VARCHAR(100) UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);



CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  sender_name VARCHAR(100),
  receiver_name VARCHAR(100),
  receiver_account VARCHAR(100),
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','approved','failed') DEFAULT 'pending',
  payment_method VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);


CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,           -- who receives the notification
    type ENUM('payment','message','job') NOT NULL,  -- category
    reference_id INT DEFAULT NULL,  -- optional: related record ID (payment_id, message_id, job_id)
    message TEXT NOT NULL,          -- the notification text
    is_read TINYINT(1) DEFAULT 0,   -- 0 = unread, 1 = read
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
