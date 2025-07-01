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

    $errors = [];
    $success = false;

    // Обработка изменения описания
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_description') {
        $description = trim($_POST['description'] ?? '');
        if (empty($description)) {
            $errors[] = 'Описание не может быть пустым';
        } else {
            if (updateRestaurant($restaurant['id'], $restaurant['name'], $description, $restaurant['address'], $restaurant['phone'], $restaurant['cuisine_id'], $restaurant['price_range'])) {
                $success = 'Описание успешно обновлено';
                // Обновляем данные ресторана
                $restaurant = getRestaurantByAdminId($_SESSION['user_id']);
            } else {
                $errors[] = 'Ошибка при обновлении описания';
            }
        }
    }

    // Обработка загрузки фото
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_image'
        && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK
    ) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $file = $_FILES['image'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Разрешены только JPG, PNG или WEBP изображения';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Размер файла не должен превышать 2 МБ';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName = 'restaurant_' . $restaurant['id'] . '_' . time() . '.' . $ext;
            $targetPath = __DIR__ . '/../assets/img/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                if (updateRestaurantImage($restaurant['id'], $newName)) {
                    $success = 'Фото успешно обновлено';
                    $restaurant = getRestaurantByAdminId($_SESSION['user_id']);
                } else {
                    $errors[] = 'Ошибка при сохранении фото в базе данных';
                }
            } else {
                $errors[] = 'Ошибка при загрузке файла';
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки — <?= htmlspecialchars($restaurant['name']) ?></title>
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
                <a href="bookings.php"><i class="fas fa-calendar-check"></i> <span>Бронирования</span></a>
                <a href="menu.php"><i class="fas fa-utensils"></i> <span>Меню</span></a>
                <a href="reviews.php"><i class="fas fa-star"></i> <span>Отзывы</span></a>
                <a href="profile.php" class="active"><i class="fas fa-cog"></i> <span>Настройки</span></a>
            </nav>
            <a href="../index.php" class="btn btn-secondary sidebar-exit">Вернуться на сайт</a>
        </div>
    </aside>
    <main class="admin-main">
        <div class="container">
            <h1><i class="fas fa-cog"></i> Настройки</h1>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <div class="admin-restaurant-settings" style="background: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; max-width: 600px; margin: 0 auto;">
                <h3 style="margin-top: 0;">Описание ресторана</h3>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_description">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <textarea name="description" rows="5" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 0.25rem; resize: vertical;"><?= htmlspecialchars($restaurant['description']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
                </form>
            </div>
            <div class="admin-restaurant-settings" style="background: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; max-width: 600px; margin: 0 auto; margin-top:2rem;">
                <h3 style="margin-top: 0;">Фото ресторана</h3>
                <div style="margin-bottom: 1rem; text-align:center;">
                    <?php if (!empty($restaurant['image'])): ?>
                        <img src="../assets/img/<?= htmlspecialchars($restaurant['image']) ?>" alt="Фото ресторана" style="max-width: 100%; max-height: 240px; border-radius: 0.5rem; box-shadow:0 2px 8px #0001;">
                    <?php else: ?>
                        <div style="color:#888; font-size:1.1rem;">Фото не загружено</div>
                    <?php endif; ?>
                </div>
                <form method="POST" action="profile.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_image">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required style="margin-bottom:1rem;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Загрузить фото</button>
                </form>
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