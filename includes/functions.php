<?php
require_once __DIR__ . '/../config/database.php';

// Функции для работы с ресторанами
function getRestaurants($search = '', $cuisine = '', $price_range = '', $rating = '') {
    $sql = "SELECT r.*, c.name as cuisine_name, 
            COALESCE(AVG(rev.rating), 0) as rating,
            COUNT(rev.id) as rating_count
            FROM restaurants r 
            LEFT JOIN cuisines c ON r.cuisine_id = c.id
            LEFT JOIN reviews rev ON r.id = rev.restaurant_id
            WHERE 1=1";
    
    $params = [];
    
    if ($search) {
        $sql .= " AND (r.name LIKE ? OR r.description LIKE ? OR r.address LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($cuisine) {
        $sql .= " AND r.cuisine_id = ?";
        $params[] = $cuisine;
    }
    
    if ($price_range) {
        $sql .= " AND r.price_range = ?";
        $params[] = $price_range;
    }
    
    $sql .= " GROUP BY r.id";
    
    if ($rating) {
        $sql .= " HAVING rating >= ?";
        $params[] = $rating;
    }
    
    $sql .= " ORDER BY rating DESC, r.name ASC";
    
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

function getRestaurantById($id) {
    $sql = "SELECT r.*, c.name as cuisine_name,
            COALESCE(AVG(rev.rating), 0) as rating,
            COUNT(rev.id) as rating_count
            FROM restaurants r 
            LEFT JOIN cuisines c ON r.cuisine_id = c.id
            LEFT JOIN reviews rev ON r.id = rev.restaurant_id
            WHERE r.id = ?
            GROUP BY r.id";
    
    $stmt = executeQuery($sql, [$id]);
    return $stmt->fetch();
}

function getCuisines() {
    $sql = "SELECT * FROM cuisines ORDER BY name";
    $stmt = executeQuery($sql);
    return $stmt->fetchAll();
}

// Функции для работы с пользователями
function registerUser($email, $password, $name, $phone) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (email, password, name, phone, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = executeQuery($sql, [$email, $hashedPassword, $name, $phone]);
    
    return $stmt->rowCount() > 0;
}

function loginUser($email, $password) {
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = executeQuery($sql, [$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

function getUserById($id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = executeQuery($sql, [$id]);
    return $stmt->fetch();
}

// Функции для работы с бронированиями
function createBooking($userId, $restaurantId, $date, $time, $guests, $specialRequests = '') {
    $sql = "INSERT INTO bookings (user_id, restaurant_id, booking_date, booking_time, guests, special_requests, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW())";
    
    $stmt = executeQuery($sql, [$userId, $restaurantId, $date, $time, $guests, $specialRequests]);
    return $stmt->rowCount() > 0;
}

function getUserBookings($userId) {
    $sql = "SELECT b.*, r.name as restaurant_name, r.address as restaurant_address
            FROM bookings b
            JOIN restaurants r ON b.restaurant_id = r.id
            WHERE b.user_id = ?
            ORDER BY b.booking_date DESC, b.booking_time DESC";
    
    $stmt = executeQuery($sql, [$userId]);
    return $stmt->fetchAll();
}

function getRestaurantBookings($restaurantId) {
    $sql = "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.restaurant_id = ?
            ORDER BY b.booking_date ASC, b.booking_time ASC";
    
    $stmt = executeQuery($sql, [$restaurantId]);
    return $stmt->fetchAll();
}

function cancelBooking($bookingId, $userId) {
    $sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?";
    $stmt = executeQuery($sql, [$bookingId, $userId]);
    return $stmt->rowCount() > 0;
}

function updateBookingStatus($bookingId, $status) {
    $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = executeQuery($sql, [$status, $bookingId]);
    return $stmt->rowCount() > 0;
}

function updateBooking($bookingId, $userId, $date, $time, $guests, $specialRequests = '') {
    $sql = "UPDATE bookings SET booking_date = ?, booking_time = ?, guests = ?, special_requests = ? WHERE id = ? AND user_id = ?";
    $stmt = executeQuery($sql, [$date, $time, $guests, $specialRequests, $bookingId, $userId]);
    return $stmt->rowCount() > 0;
}

// Функции для работы с отзывами
function addReview($userId, $restaurantId, $rating, $comment) {
    $sql = "INSERT INTO reviews (user_id, restaurant_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = executeQuery($sql, [$userId, $restaurantId, $rating, $comment]);
    return $stmt->rowCount() > 0;
}

function getRestaurantReviews($restaurantId) {
    $sql = "SELECT r.*, u.name as user_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.restaurant_id = ?
            ORDER BY r.created_at DESC";
    
    $stmt = executeQuery($sql, [$restaurantId]);
    return $stmt->fetchAll();
}

// Функции для работы с меню
function getRestaurantMenu($restaurantId) {
    $sql = "SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name";
    $stmt = executeQuery($sql, [$restaurantId]);
    return $stmt->fetchAll();
}

function addMenuItem($restaurantId, $name, $description, $price, $category) {
    $sql = "INSERT INTO menu_items (restaurant_id, name, description, price, category) VALUES (?, ?, ?, ?, ?)";
    $stmt = executeQuery($sql, [$restaurantId, $name, $description, $price, $category]);
    return $stmt->rowCount() > 0;
}

function updateMenuItem($id, $restaurantId, $name, $description, $price, $category) {
    $sql = "UPDATE menu_items SET name = ?, description = ?, price = ?, category = ? WHERE id = ? AND restaurant_id = ?";
    $stmt = executeQuery($sql, [$name, $description, $price, $category, $id, $restaurantId]);
    return $stmt->rowCount() > 0;
}

function deleteMenuItem($id, $restaurantId) {
    $sql = "DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?";
    $stmt = executeQuery($sql, [$id, $restaurantId]);
    return $stmt->rowCount() > 0;
}

// Функции для администраторов ресторанов
function getRestaurantByAdminId($adminId) {
    $sql = "SELECT * FROM restaurants WHERE admin_id = ?";
    $stmt = executeQuery($sql, [$adminId]);
    return $stmt->fetch();
}

function updateRestaurant($id, $name, $description, $address, $phone, $cuisineId, $priceRange) {
    $sql = "UPDATE restaurants SET name = ?, description = ?, address = ?, phone = ?, cuisine_id = ?, price_range = ? WHERE id = ?";
    $stmt = executeQuery($sql, [$name, $description, $address, $phone, $cuisineId, $priceRange, $id]);
    return $stmt->rowCount() > 0;
}

function updateRestaurantImage($id, $image) {
    $sql = "UPDATE restaurants SET image = ? WHERE id = ?";
    $stmt = executeQuery($sql, [$image, $id]);
    return $stmt->rowCount() > 0;
}

// Вспомогательные функции
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isRestaurantAdmin($restaurantId) {
    if (!isLoggedIn()) return false;
    
    $restaurant = getRestaurantById($restaurantId);
    return $restaurant && $restaurant['admin_id'] == $_SESSION['user_id'];
}

function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}

function getBookingStatusText($status) {
    $statuses = [
        'confirmed' => 'Подтверждено',
        'pending' => 'Ожидает подтверждения',
        'cancelled' => 'Отменено',
        'completed' => 'Завершено'
    ];
    
    return $statuses[$status] ?? $status;
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateTime($time) {
    $t = DateTime::createFromFormat('H:i', $time);
    return $t && $t->format('H:i') === $time;
} 