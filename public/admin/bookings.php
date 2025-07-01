<?php
    session_start();
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/functions.php';
    if (!isLoggedIn() || $_SESSION['user_role'] !== 'restaurant_admin') {
        header('Location: ../login.php');
        exit;
    }
    $restaurant = getRestaurantByAdminId($_SESSION['user_id']);
    if (!$restaurant) {
        die("Ошибка: У вас нет привязанного ресторана. Обратитесь к администратору системы.");
    }
    $bookings = getRestaurantBookings($restaurant['id']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирования — <?= htmlspecialchars($restaurant['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-logo"><i class="fas fa-store"></i></div>
        <div class="sidebar-menu">
            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Дашборд</span></a>
                <a href="bookings.php" class="active"><i class="fas fa-calendar-check"></i> <span>Бронирования</span></a>
                <a href="menu.php"><i class="fas fa-utensils"></i> <span>Меню</span></a>
                <a href="reviews.php"><i class="fas fa-star"></i> <span>Отзывы</span></a>
                <a href="profile.php"><i class="fas fa-cog"></i> <span>Настройки</span></a>
            </nav>
            <a href="../index.php" class="btn btn-secondary sidebar-exit">Вернуться на сайт</a>
        </div>
    </aside>
    <main class="admin-main">
        <div class="container">
            <h1><i class="fas fa-calendar-check"></i> Бронирования</h1>
            <?php if (empty($bookings)): ?>
                <p>Пока нет бронирований.</p>
            <?php else: ?>
                <div class="bookings-list-admin">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-admin-item">
                            <div class="booking-admin-header">
                                <strong><?= htmlspecialchars($booking['user_name']) ?></strong>
                                <span><?= formatDate($booking['booking_date']) ?>, <?= formatTime($booking['booking_time']) ?></span>
                            </div>
                            <div class="booking-admin-details">
                                <span>Гостей: <?= $booking['guests'] ?></span>
                                <span>Статус: <?= getBookingStatusText($booking['status']) ?></span>
                                <?php if (!empty($booking['special_requests'])): ?>
                                    <span>Пожелания: <?= htmlspecialchars($booking['special_requests']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="booking-admin-contacts">
                                <span>Email: <?= htmlspecialchars($booking['user_email']) ?></span>
                                <span>Телефон: <?= htmlspecialchars($booking['user_phone']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<footer class="modern-footer">
    <div class="container">
        <p>&copy; 2024 РесторанБукер. Все права защищены.</p>
    </div>
</footer>
</body>
</html> 