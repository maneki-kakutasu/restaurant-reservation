<?php
    session_start();
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';

    $restaurant_id = $_GET['id'] ?? 0;
    $restaurant = getRestaurantById($restaurant_id);

    if (!$restaurant) {
        header('Location: index.php');
        exit;
    }

    $reviews = getRestaurantReviews($restaurant_id);
    $menu = getRestaurantMenu($restaurant_id);
    $cuisines = getCuisines();

    $errors = [];
    $success = false;

    // Обработка бронирования
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'book') {
            if (!isLoggedIn()) {
                $errors[] = 'Для бронирования необходимо войти в систему';
            } else {
                $date = $_POST['date'] ?? '';
                $time = $_POST['time'] ?? '';
                $guests = (int)($_POST['guests'] ?? 0);
                $special_requests = trim($_POST['special_requests'] ?? '');
                
                // Валидация
                if (empty($date)) {
                    $errors[] = 'Выберите дату';
                } elseif (!validateDate($date)) {
                    $errors[] = 'Некорректная дата';
                } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
                    $errors[] = 'Нельзя бронировать на прошедшую дату';
                }
                
                if (empty($time)) {
                    $errors[] = 'Выберите время';
                } elseif (!validateTime($time)) {
                    $errors[] = 'Некорректное время';
                }
                
                if ($guests < 1 || $guests > 20) {
                    $errors[] = 'Количество гостей должно быть от 1 до 20';
                }
                
                if (empty($errors)) {
                    if (createBooking($_SESSION['user_id'], $restaurant_id, $date, $time, $guests, $special_requests)) {
                        $success = true;
                    } else {
                        $errors[] = 'Ошибка при создании бронирования';
                    }
                }
            }
        } elseif ($_POST['action'] === 'review') {
            if (!isLoggedIn()) {
                $errors[] = 'Для оставления отзыва необходимо войти в систему';
            } else {
                $rating = (int)($_POST['rating'] ?? 0);
                $comment = trim($_POST['comment'] ?? '');
                
                if ($rating < 1 || $rating > 5) {
                    $errors[] = 'Рейтинг должен быть от 1 до 5';
                }
                
                if (empty($comment)) {
                    $errors[] = 'Комментарий обязателен';
                }
                
                if (empty($errors)) {
                    if (addReview($_SESSION['user_id'], $restaurant_id, $rating, $comment)) {
                        header("Location: restaurant.php?id=$restaurant_id");
                        exit;
                    } else {
                        $errors[] = 'Ошибка при добавлении отзыва';
                    }
                }
            }
        }
    }

    include __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name']) ?> - РесторанБукер</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    .rating-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-start;
        gap: 0.2em;
    }
    .rating-input input[type="radio"] {
        display: none;
    }
    .rating-input label {
        cursor: pointer;
        font-size: 2rem;
        color: #ccc;
        transition: color 0.2s;
    }
    .rating-input input[type="radio"]:checked ~ label,
    .rating-input label:hover,
    .rating-input label:hover ~ label {
        color: #ffd700;
    }
    .rating-input input[type="radio"]:checked + label {
        color: #ffd700;
    }

    .modal-content {
        position: relative;
    }
    </style>
</head>
<body>
    <main>
        <div class="container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Бронирование успешно создано! Вы можете просмотреть его в своем <a href="profile.php">профиле</a>.</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="restaurant-detail">
                <div class="restaurant-header">
                    <div class="restaurant-image">
                        <?php if ($restaurant['image']): ?>
                            <img src="uploads/restaurants/<?= htmlspecialchars($restaurant['image']) ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-utensils"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="restaurant-info">
                        <h1><?= htmlspecialchars($restaurant['name']) ?></h1>
                        <p class="cuisine"><?= htmlspecialchars($restaurant['cuisine_name']) ?></p>
                        <p class="address">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($restaurant['address']) ?>
                        </p>
                        <?php if ($restaurant['phone']): ?>
                            <p class="phone">
                                <i class="fas fa-phone"></i>
                                <a href="tel:<?= htmlspecialchars($restaurant['phone']) ?>"><?= htmlspecialchars($restaurant['phone']) ?></a>
                            </p>
                        <?php endif; ?>
                        
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $restaurant['rating'] ? 'filled' : '' ?>"></i>
                            <?php endfor; ?>
                            <span>(<?= $restaurant['rating_count'] ?> отзывов)</span>
                        </div>
                        
                        <p class="price-range" style="display:flex; align-items:center; gap:0.15em;">
                            <?php
                            $max_price_icons = 4;
                            $active_icons = (int)$restaurant['price_range'];
                            for ($i = 1; $i <= $max_price_icons; $i++):
                                $opacity = $i <= $active_icons ? 1 : 0.25;
                            ?>
                                <span style="display:inline-block; width:22px; height:22px; vertical-align:middle; opacity:<?= $opacity ?>;">
                                    <img src="assets/img/ruble_currency_icon_215811.svg" alt="₽" style="width:100%;height:100%;display:block;filter:<?= $opacity < 1 ? 'grayscale(1)' : 'none' ?>;">
                                </span>
                            <?php endfor; ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($restaurant['description']): ?>
                    <div class="restaurant-description">
                        <h3>О ресторане</h3>
                        <p><?= nl2br(htmlspecialchars($restaurant['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="restaurant-actions">
                    <button class="btn btn-primary" onclick="showBookingForm()">
                        <i class="fas fa-calendar-plus"></i> Забронировать столик
                    </button>
                    
                    <?php if (isLoggedIn()): ?>
                        <button class="btn btn-secondary" onclick="showReviewForm()">
                            <i class="fas fa-star"></i> Оставить отзыв
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Форма бронирования -->
            <div id="bookingForm" class="modal" style="display: none;">
                <div class="modal-content">
                    <?php if (!isLoggedIn()): ?>
                        <div style="font-size:1.15rem; color:#222; text-align:center; padding:2.5rem 1rem;">
                            Для бронирования необходимо <a href="login.php" style="color:#5a32c2;text-decoration:underline;">войти в систему</a>.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="restaurant.php?id=<?= $restaurant_id ?>">
                            <input type="hidden" name="action" value="book">
                            
                            <div class="form-group">
                                <label for="date">Дата *</label>
                                <input type="date" id="date" name="date" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="time">Время *</label>
                                <select id="time" name="time" required>
                                    <option value="">Выберите время</option>
                                    <option value="12:00">12:00</option>
                                    <option value="13:00">13:00</option>
                                    <option value="14:00">14:00</option>
                                    <option value="18:00">18:00</option>
                                    <option value="19:00">19:00</option>
                                    <option value="20:00">20:00</option>
                                    <option value="21:00">21:00</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="guests">Количество гостей *</label>
                                <select id="guests" name="guests" required>
                                    <option value="">Выберите количество</option>
                                    <?php for ($i = 1; $i <= 20; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> <?= $i == 1 ? 'гость' : ($i < 5 ? 'гостя' : 'гостей') ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="special_requests">Особые пожелания</label>
                                <textarea id="special_requests" name="special_requests" rows="3" placeholder="Например: столик у окна, детский стул..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Забронировать</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Форма отзыва -->
            <div id="reviewForm" class="modal" style="display: none;">
                <div class="modal-content">
                    <h3>Оставить отзыв</h3>
                    
                    <?php if (!isLoggedIn()): ?>
                        <p>Для оставления отзыва необходимо <a href="login.php">войти в систему</a>.</p>
                    <?php else: ?>
                        <form method="POST" action="restaurant.php?id=<?= $restaurant_id ?>">
                            <input type="hidden" name="action" value="review">
                            
                            <div class="form-group">
                                <label for="rating">Оценка *</label>
                                <div class="rating-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                        <label for="star<?= $i ?>" title="<?= $i ?> звезда<?= $i > 1 ? 'ы' : '' ?>">&#9733;</label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comment">Комментарий *</label>
                                <textarea id="comment" name="comment" rows="4" required placeholder="Поделитесь своими впечатлениями..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Отправить отзыв</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Меню ресторана -->
            <?php if (!empty($menu)): ?>
                <section class="restaurant-menu">
                    <h2>Меню</h2>
                    <div class="menu-grid">
                        <?php foreach ($menu as $item): ?>
                            <div class="menu-item">
                                <div class="menu-item-info">
                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                    <?php if ($item['description']): ?>
                                        <p><?= htmlspecialchars($item['description']) ?></p>
                                    <?php endif; ?>
                                    <span class="price"><?= number_format($item['price'], 0, ',', ' ') ?> ₽</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
            
            <!-- Отзывы -->
            <section class="restaurant-reviews">
                <h2>Отзывы (<?= count($reviews) ?>)</h2>
                
                <?php if (empty($reviews)): ?>
                    <p>Пока нет отзывов. Будьте первым!</p>
                <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $review['rating'] ? ' filled' : '' ?>" style="color:#ffd700; opacity:<?= $i <= $review['rating'] ? '1' : '0.3' ?>;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="review-author">
                                        <strong><?= htmlspecialchars($review['user_name']) ?></strong>
                                        <span class="review-date"><?= formatDate($review['created_at']) ?></span>
                                    </div>
                                </div>
                                <div class="review-comment">
                                    <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        function showBookingForm() {
            document.getElementById('bookingForm').style.display = 'block';
        }
        
        function hideBookingForm() {
            document.getElementById('bookingForm').style.display = 'none';
        }
        
        function showReviewForm() {
            document.getElementById('reviewForm').style.display = 'block';
        }
        
        function hideReviewForm() {
            document.getElementById('reviewForm').style.display = 'none';
        }
        
        // Закрытие модальных окон при клике вне их
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html> 