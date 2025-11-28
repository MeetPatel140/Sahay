-- Sahay Database Schema

-- Users Table (Stores both Customers and Helpers)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    user_type ENUM('customer', 'helper') NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Helper Profiles (Extra info for workers)
CREATE TABLE helper_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    skill_tags VARCHAR(255),
    base_rate DECIMAL(10, 2),
    current_lat DECIMAL(10, 8),
    current_lng DECIMAL(11, 8),
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Tasks Table
CREATE TABLE tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    helper_id INT NULL,
    description TEXT,
    voice_note_url VARCHAR(255),
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    agreed_price DECIMAL(10, 2),
    pickup_lat DECIMAL(10, 8),
    pickup_lng DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id)
);