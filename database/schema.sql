-- Создание базы данных
CREATE DATABASE IF NOT EXISTS restaurant_booker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_booker;

-- Таблица пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin', 'restaurant_admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Таблица кухонь
CREATE TABLE cuisines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица ресторанов
CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address VARCHAR(500) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    cuisine_id INT,
    price_range ENUM('1', '2', '3', '4') DEFAULT '2',
    image VARCHAR(255),
    admin_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cuisine_id) REFERENCES cuisines(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Таблица столиков
CREATE TABLE tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    table_number VARCHAR(10) NOT NULL,
    capacity INT NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Таблица бронирований
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    table_id INT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    guests INT NOT NULL,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
    FOREIGN KEY (table_id) REFERENCES tables(id)
);

-- Таблица отзывов
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
);

-- Таблица меню
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Вставка тестовых данных

-- Кухни
INSERT INTO cuisines (name, description) VALUES
('Итальянская', 'Паста, пицца, ризотто и другие блюда итальянской кухни'),
('Японская', 'Суши, роллы, сашими и другие блюда японской кухни'),
('Русская', 'Традиционные блюда русской кухни'),
('Китайская', 'Блюда китайской кухни'),
('Французская', 'Изысканные блюда французской кухни'),
('Мексиканская', 'Острые и ароматные блюда мексиканской кухни'),
('Индийская', 'Пряные блюда индийской кухни'),
('Американская', 'Классические блюда американской кухни');

-- Тестовые пользователи
INSERT INTO users (email, password, name, phone, role) VALUES
('admin@restaurantbooker.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор', '+7 (999) 123-45-67', 'admin'),
('user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Иван Иванов', '+7 (999) 111-22-33', 'user'),
('restaurant1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Владелец Итальянского', '+7 (999) 444-55-66', 'restaurant_admin'),
('restaurant2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Владелец Японского', '+7 (999) 777-88-99', 'restaurant_admin');

-- Тестовые рестораны
INSERT INTO restaurants (name, description, address, phone, cuisine_id, price_range, admin_id) VALUES
('La Bella Italia', 'Аутентичный итальянский ресторан с домашней пастой и пиццей из дровяной печи', 'ул. Тверская, 15', '+7 (495) 123-45-67', 1, 3, 3),
('Sakura Sushi', 'Современный ресторан японской кухни с широким выбором суши и роллов', 'ул. Арбат, 25', '+7 (495) 234-56-78', 2, 2, 4),
('Русский Двор', 'Традиционный ресторан русской кухни в историческом центре', 'ул. Никольская, 10', '+7 (495) 345-67-89', 3, 2, 3),
('Golden Dragon', 'Ресторан китайской кухни с традиционными блюдами', 'ул. Покровка, 30', '+7 (495) 456-78-90', 4, 1, 4),
('Le Petit Paris', 'Элегантный французский ресторан с изысканным меню', 'ул. Кузнецкий Мост, 5', '+7 (495) 567-89-01', 5, 4, 3);

-- Тестовые столики
INSERT INTO tables (restaurant_id, table_number, capacity) VALUES
(1, '1', 2), (1, '2', 4), (1, '3', 6), (1, '4', 8),
(2, '1', 2), (2, '2', 4), (2, '3', 6),
(3, '1', 2), (3, '2', 4), (3, '3', 8),
(4, '1', 2), (4, '2', 4), (4, '3', 6),
(5, '1', 2), (5, '2', 4), (5, '3', 6), (5, '4', 10);

-- Тестовые бронирования
INSERT INTO bookings (user_id, restaurant_id, booking_date, booking_time, guests, status) VALUES
(2, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:00:00', 4, 'confirmed'),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '20:00:00', 2, 'confirmed'),
(2, 3, DATE_ADD(CURDATE(), INTERVAL -1 DAY), '18:30:00', 6, 'completed');

-- Тестовые отзывы
INSERT INTO reviews (user_id, restaurant_id, rating, comment) VALUES
(2, 1, 5, 'Отличный ресторан! Паста была восхитительной, обслуживание на высоте.'),
(2, 2, 4, 'Хорошие суши, свежая рыба. Рекомендую!'),
(2, 3, 5, 'Атмосферный ресторан с традиционной русской кухней.');

-- Тестовое меню для ресторана La Bella Italia
INSERT INTO menu_items (restaurant_id, name, description, price, category) VALUES
(1, 'Карбонара', 'Паста с беконом, яйцом и сыром пармезан', 850.00, 'Паста'),
(1, 'Маргарита', 'Классическая пицца с томатами и моцареллой', 750.00, 'Пицца'),
(1, 'Ризотто с грибами', 'Кремовое ризотто с белыми грибами', 950.00, 'Основные блюда'),
(1, 'Тирамису', 'Классический итальянский десерт', 450.00, 'Десерты');

-- Тестовое меню для ресторана Sakura Sushi
INSERT INTO menu_items (restaurant_id, name, description, price, category) VALUES
(2, 'Филадельфия ролл', 'Ролл с лососем, сливочным сыром и огурцом', 650.00, 'Роллы'),
(2, 'Калифорния ролл', 'Ролл с крабом, авокадо и огурцом', 550.00, 'Роллы'),
(2, 'Лосось сашими', 'Свежий лосось сашими', 450.00, 'Сашими'),
(2, 'Мисо суп', 'Традиционный японский суп', 350.00, 'Супы'); 