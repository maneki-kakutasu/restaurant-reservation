<?php
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);
    session_start();
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Получение списка ресторанов с фильтрацией
    $search = $_GET['search'] ?? '';
    $cuisine = $_GET['cuisine'] ?? '';
    $price_range = $_GET['price_range'] ?? '';
    $rating = $_GET['rating'] ?? '';

    $restaurants = getRestaurants($search, $cuisine, $price_range, $rating);
    $cuisines = getCuisines();

    include __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>РесторанБукер - Бронирование столиков</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <main>
        <section class="hero modern-hero" style="background: #f7f8fa; min-height:unset;">
            <div class="hero-content">
                <h1>Все рестораны</h1>
                <p>Откройте для себя лучшие заведения города и бронируйте столики онлайн</p>
                <form class="search-form" method="GET" action="index.php">
                    <input type="text" name="search" placeholder="Поиск ресторанов..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Найти
                    </button>
                </form>
                <div class="filters modern-filters" style="margin-top:1.5rem;">
                    <form method="GET" action="index.php" class="filter-form" id="filterForm">
                        <div class="filter-group">
                            <label for="cuisine"><i class="fas"></i> Кухня:</label>
                            <select name="cuisine" id="cuisine">
                                <option value="">Все кухни</option>
                                <?php foreach ($cuisines as $cuisine_item): ?>
                                    <option value="<?= $cuisine_item['id'] ?>" <?= $cuisine == $cuisine_item['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cuisine_item['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="price_range"><i class="fas"></i> Ценовой диапазон:</label>
                            <select name="price_range" id="price_range">
                                <option value="">Любой</option>
                                <option value="1" <?= $price_range == '1' ? 'selected' : '' ?>>$ (до 1000₽)</option>
                                <option value="2" <?= $price_range == '2' ? 'selected' : '' ?>>$$ (1000-3000₽)</option>
                                <option value="3" <?= $price_range == '3' ? 'selected' : '' ?>>$$$ (3000-5000₽)</option>
                                <option value="4" <?= $price_range == '4' ? 'selected' : '' ?>>$$$$ (от 5000₽)</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="rating"><i class="fas"></i> Рейтинг:</label>
                            <select name="rating" id="rating">
                                <option value="">Любой</option>
                                <option value="4" <?= $rating == '4' ? 'selected' : '' ?>>4+ звезд</option>
                                <option value="3" <?= $rating == '3' ? 'selected' : '' ?>>3+ звезд</option>
                                <option value="2" <?= $rating == '2' ? 'selected' : '' ?>>2+ звезд</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:0.7rem; align-items:center;">
                            <button type="submit" class="btn btn-secondary">Применить фильтры</button>
                            <button type="button" class="btn btn-secondary" style="background:#eee;color:#222;border:1.5px solid #bbb;" onclick="window.location='index.php'">Сбросить фильтры</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="restaurants modern-restaurants">
            <div class="container">
                <h2>Рестораны</h2>
                <div class="restaurants-grid">
                    <?php if (empty($restaurants)): ?>
                        <div class="no-results">
                            <p>Рестораны не найдены</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($restaurants as $restaurant): ?>
                            <div class="restaurant-card modern-card">
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
                                    <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                                    <p class="cuisine"><i class="fas fa-bowl-food"></i> <?= htmlspecialchars($restaurant['cuisine_name']) ?></p>
                                    <p class="address">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($restaurant['address']) ?>
                                    </p>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $restaurant['rating'] ? 'filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                        <span>(<?= $restaurant['rating_count'] ?> отзывов)</span>
                                    </div>
                                    <p class="price-range" style="display:flex; align-items:center; justify-content:center; gap:0.15em;">
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
                                    <?php if (!empty($restaurant['description'])): ?>
                                        <p class="restaurant-short-desc" style="color:#666; font-size:0.98rem; margin:0.5rem 0 0.7rem 0; min-height:40px;">
                                            <?= htmlspecialchars(mb_strimwidth($restaurant['description'], 0, 90, '...')) ?>
                                        </p>
                                    <?php endif; ?>
                                    <div style="display:flex; gap:0.5rem; justify-content:center; margin-top:0.5rem;">
                                        <a href="restaurant.php?id=<?= $restaurant['id'] ?>" class="btn btn-primary" style="min-width:120px;">Подробнее</a>
                                        <a href="restaurant.php?id=<?= $restaurant['id'] ?>#bookingForm" class="btn btn-secondary" style="min-width:120px;">Забронировать</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        // Burger menu for mobile
        const burger = document.getElementById('burgerMenu');
        const navMenu = document.getElementById('navMenu');
        burger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            burger.classList.toggle('active');
        });

        // Price range SVG filter
        const priceIcons = document.querySelectorAll('.price-range-icon');
        const priceInput = document.getElementById('priceRangeInput');
        if (priceIcons.length && priceInput) {
            priceIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    const val = parseInt(this.getAttribute('data-value'));
                    priceInput.value = val;
                    priceIcons.forEach((ic, idx) => {
                        ic.style.opacity = (idx < val) ? '1' : '0.25';
                        ic.querySelector('img').style.filter = (idx < val) ? 'none' : 'grayscale(1)';
                    });
                });
            });
        }
    </script>
</body>
</html> 