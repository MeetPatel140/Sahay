-- Sahayak Database Schema - Production Ready
-- Version: 1.1 with sample data

-- Users Table (Stores both Customers and Helpers)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('customer', 'helper') NOT NULL,
    is_verified BOOLEAN DEFAULT TRUE,
    wallet_balance DECIMAL(10, 2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Helper Profiles (Extra info for workers)
CREATE TABLE helper_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_tags VARCHAR(255) NOT NULL,
    base_rate DECIMAL(10, 2) NOT NULL,
    current_lat DECIMAL(10, 8) DEFAULT 0,
    current_lng DECIMAL(11, 8) DEFAULT 0,
    is_available BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3, 2) DEFAULT 5.00,
    total_jobs INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Tasks Table
CREATE TABLE tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    helper_id INT NULL,
    description TEXT NOT NULL,
    voice_note_url VARCHAR(255) NULL,
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    agreed_price DECIMAL(10, 2) NOT NULL,
    pickup_lat DECIMAL(10, 8) DEFAULT 0,
    pickup_lng DECIMAL(11, 8) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (helper_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Task Messages (Chat between customer and helper)
CREATE TABLE task_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Wallet Transactions
CREATE TABLE wallet_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE SET NULL
);

-- Insert Sample Data for Testing

-- Sample Users (Customers)
INSERT INTO users (phone, password_hash, full_name, user_type, wallet_balance) VALUES
('9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rahul Sharma', 'customer', 500.00),
('9876543211', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Singh', 'customer', 750.00),
('9876543212', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amit Kumar', 'customer', 300.00);

-- Sample Users (Helpers)
INSERT INTO users (phone, password_hash, full_name, user_type, wallet_balance) VALUES
('9876543220', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ravi Electrician', 'helper', 1200.00),
('9876543221', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Suresh Plumber', 'helper', 800.00),
('9876543222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mohan Cleaner', 'helper', 600.00),
('9876543223', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Deepak Carpenter', 'helper', 950.00);

-- Sample Helper Profiles
INSERT INTO helper_profiles (user_id, skill_tags, base_rate, current_lat, current_lng, is_available, rating, total_jobs) VALUES
(4, 'Electrician, Wiring, Fan Installation', 150.00, 28.6139, 77.2090, TRUE, 4.8, 45),
(5, 'Plumber, Pipe Repair, Bathroom Fitting', 120.00, 28.6129, 77.2080, TRUE, 4.6, 38),
(6, 'House Cleaning, Office Cleaning, Deep Cleaning', 80.00, 28.6149, 77.2100, TRUE, 4.9, 67),
(7, 'Carpenter, Furniture Repair, Wood Work', 180.00, 28.6159, 77.2110, FALSE, 4.7, 29);

-- Sample Tasks
INSERT INTO tasks (customer_id, helper_id, description, status, agreed_price, pickup_lat, pickup_lng) VALUES
(1, 4, 'Need to fix ceiling fan in bedroom. Fan is not working properly.', 'completed', 200.00, 28.6139, 77.2090),
(2, NULL, 'Kitchen sink is leaking. Need urgent plumber.', 'pending', 150.00, 28.6129, 77.2080),
(3, 6, 'House cleaning for 2BHK apartment. Deep cleaning required.', 'in_progress', 500.00, 28.6149, 77.2100),
(1, NULL, 'Wooden door handle is broken. Need carpenter.', 'pending', 300.00, 28.6139, 77.2090);

-- Sample Wallet Transactions
INSERT INTO wallet_transactions (user_id, task_id, amount, transaction_type, description) VALUES
(1, 1, 200.00, 'debit', 'Payment for ceiling fan repair'),
(4, 1, 180.00, 'credit', 'Earnings from ceiling fan repair (after 10% commission)'),
(3, 3, 500.00, 'debit', 'Payment for house cleaning service'),
(6, 3, 450.00, 'credit', 'Earnings from house cleaning (after 10% commission)');

-- Create Indexes for Better Performance
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_helper_profiles_location ON helper_profiles(current_lat, current_lng);
CREATE INDEX idx_helper_profiles_available ON helper_profiles(is_available);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_location ON tasks(pickup_lat, pickup_lng);
CREATE INDEX idx_tasks_customer ON tasks(customer_id);
CREATE INDEX idx_tasks_helper ON tasks(helper_id);

-- Note: Default password for all sample users is 'password123'
-- In production, users will register with their own passwords
