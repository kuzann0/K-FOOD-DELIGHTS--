-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create menu items table
CREATE TABLE IF NOT EXISTS menu_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    preparation_time INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Insert some sample categories
INSERT INTO categories (name, description, is_active) VALUES
('Main Dishes', 'Korean main course meals', 1),
('Appetizers', 'Starters and side dishes', 1),
('Beverages', 'Drinks and refreshments', 1),
('Desserts', 'Sweet treats and desserts', 1);

-- Insert some sample menu items
INSERT INTO menu_items (category_id, name, description, price, is_available, preparation_time) VALUES
(1, 'Bibimbap', 'Mixed rice bowl with vegetables and beef', 12.99, 1, 15),
(1, 'Bulgogi', 'Marinated beef barbecue', 15.99, 1, 20),
(2, 'Kimchi', 'Traditional fermented vegetables', 5.99, 1, 5),
(3, 'Soju', 'Korean traditional alcohol', 8.99, 1, 2),
(4, 'Bingsu', 'Shaved ice dessert with red beans', 9.99, 1, 10);
