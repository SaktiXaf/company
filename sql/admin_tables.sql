-- Additional tables for admin functionality

-- Create feedback table if not exists
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample feedback
INSERT INTO feedback (name, email, subject, message, status) VALUES 
('John Doe', 'john@example.com', 'Great products!', 'I love the new iPhone features. The camera quality is amazing!', 'reviewed'),
('Jane Smith', 'jane@example.com', 'Suggestion for improvement', 'Could you add more color options for the iPad?', 'pending'),
('Mike Johnson', 'mike@example.com', 'Delivery issue', 'My order took longer than expected to arrive.', 'resolved');