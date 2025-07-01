<?php
    session_start();
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/functions.php';

    // Проверка прав доступа
    if (!isLoggedIn() || $_SESSION['user_role'] !== 'restaurant_admin') {
        header('Location: ../login.php');
        exit;
    }

    // Получение информации о ресторане админа
    $restaurant = getRestaurantByAdminId($_SESSION['user_id']);
    if (!$restaurant) {
        die("Ошибка: У вас нет привязанного ресторана. Обратитесь к администратору системы.");
    }

    // Статистика
    $today = date('Y-m-d');
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('sunday this week'));

    // Бронирования сегодня
    $sql_today = "SELECT COUNT(*) as count FROM bookings WHERE restaurant_id = ? AND booking_date = ? AND status != 'cancelled'";
    $stmt_today = executeQuery($sql_today, [$restaurant['id'], $today]);
    $bookings_today = $stmt_today->fetch()['count'];

    // Бронирования на этой неделе
    $sql_week = "SELECT COUNT(*) as count FROM bookings WHERE restaurant_id = ? AND booking_date BETWEEN ? AND ? AND status != 'cancelled'";
    $stmt_week = executeQuery($sql_week, [$restaurant['id'], $week_start, $week_end]);
    $bookings_week = $stmt_week->fetch()['count'];

    // Общее количество отзывов
    $sql_reviews = "SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM reviews WHERE restaurant_id = ?";
    $stmt_reviews = executeQuery($sql_reviews, [$restaurant['id']]);
    $reviews_data = $stmt_reviews->fetch();
    $reviews_count = $reviews_data['count'];
    $avg_rating = round($reviews_data['avg_rating'], 1);

    // Последние бронирования
    $sql_recent = "SELECT b.*, u.name as user_name, u.phone as user_phone 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.restaurant_id = ? 
                ORDER BY b.created_at DESC 
                LIMIT 5";
    $stmt_recent = executeQuery($sql_recent, [$restaurant['id']]);
    $recent_bookings = $stmt_recent->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления — <?= htmlspecialchars($restaurant['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- <header class="admin-header">
        <div class="container">
            <div class="admin-header-content">
                <div class="admin-header-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div>
                    <div class="admin-header-title">Панель управления</div>
                    <div class="admin-header-sub">Ресторан: <b><?= htmlspecialchars($restaurant['name']) ?></b></div>
                </div>
            </div>
        </div>
    </header> -->

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-store"></i>
            </div>
            <div class="sidebar-menu">
                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Дашборд</span></a>
                    <a href="bookings.php"><i class="fas fa-calendar-check"></i> <span>Бронирования</span></a>
                    <a href="menu.php"><i class="fas fa-utensils"></i> <span>Меню</span></a>
                    <a href="reviews.php"><i class="fas fa-star"></i> <span>Отзывы</span></a>
                    <a href="profile.php"><i class="fas fa-cog"></i> <span>Настройки</span></a>
                </nav>
                <a href="../index.php" class="btn btn-secondary sidebar-exit">Вернуться на сайт</a>
            </div>
        </aside>
        <main class="admin-main">
            <div class="container">
                <!-- Статистика -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-calendar-day"></i>
                        <span class="stat-label">Бронирований сегодня</span>
                        <span class="stat-number"><?= $bookings_today ?></span>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar-week"></i>
                        <span class="stat-label">Бронирований на неделе</span>
                        <span class="stat-number"><?= $bookings_week ?></span>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-star"></i>
                        <span class="stat-label">Отзывов</span>
                        <span class="stat-number"><?= $reviews_count ?></span>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-star-half-alt"></i>
                        <span class="stat-label">Средний рейтинг</span>
                        <span class="stat-number"><?= $avg_rating ?: '0' ?></span>
                    </div>
                </div>

                <!-- Последние бронирования -->
                <div class="recent-bookings">
                    <h2><i class="fas fa-clock"></i> Последние бронирования</h2>
                    <?php if (empty($recent_bookings)): ?>
                        <p style="text-align: center; color: #666;">Пока нет бронирований</p>
                    <?php else: ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <div class="booking-item">
                                <div class="booking-info">
                                    <h4><?= htmlspecialchars($booking['user_name']) ?></h4>
                                    <div class="booking-details">
                                        <i class="fas fa-calendar"></i> <?= formatDate($booking['booking_date']) ?> 
                                        <i class="fas fa-clock"></i> <?= formatTime($booking['booking_time']) ?>
                                        <i class="fas fa-users"></i> <?= $booking['guests'] ?> гостей
                                        <?php if ($booking['user_phone']): ?>
                                            <i class="fas fa-phone"></i> <?= htmlspecialchars($booking['user_phone']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="booking-status status-<?= $booking['status'] ?>">
                                    <?= htmlspecialchars(getBookingStatusText($booking['status'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
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