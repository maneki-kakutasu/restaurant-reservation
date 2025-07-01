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

    // Обработка действий с меню
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $category = trim($_POST['category'] ?? '');
            
            if (empty($name)) {
                $errors[] = 'Название блюда обязательно';
            }
            if ($price <= 0) {
                $errors[] = 'Цена должна быть больше 0';
            }
            if (empty($category)) {
                $errors[] = 'Категория обязательна';
            }
            
            if (empty($errors)) {
                if (addMenuItem($restaurant['id'], $name, $description, $price, $category)) {
                    $success = 'Блюдо успешно добавлено';
                } else {
                    $errors[] = 'Ошибка при добавлении блюда';
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $category = trim($_POST['category'] ?? '');
            
            if (empty($name)) {
                $errors[] = 'Название блюда обязательно';
            }
            if ($price <= 0) {
                $errors[] = 'Цена должна быть больше 0';
            }
            if (empty($category)) {
                $errors[] = 'Категория обязательна';
            }
            
            if (empty($errors)) {
                if (updateMenuItem($id, $restaurant['id'], $name, $description, $price, $category)) {
                    $success = 'Блюдо успешно обновлено';
                } else {
                    $errors[] = 'Ошибка при обновлении блюда';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            
            if (deleteMenuItem($id, $restaurant['id'])) {
                $success = 'Блюдо успешно удалено';
            } else {
                $errors[] = 'Ошибка при удалении блюда';
            }
        }
    }

    $menu = getRestaurantMenu($restaurant['id']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Меню — <?= htmlspecialchars($restaurant['name']) ?></title>
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
                <a href="menu.php" class="active"><i class="fas fa-utensils"></i> <span>Меню</span></a>
                <a href="reviews.php"><i class="fas fa-star"></i> <span>Отзывы</span></a>
                <a href="profile.php"><i class="fas fa-cog"></i> <span>Настройки</span></a>
            </nav>
            <a href="../index.php" class="btn btn-secondary sidebar-exit">Вернуться на сайт</a>
        </div>
    </aside>
    <main class="admin-main">
        <div class="container">
            <h1><i class="fas fa-utensils"></i> Меню</h1>
            
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
            
            <!-- Форма добавления нового блюда -->
            <div class="admin-menu-add" style="background: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                <h3 style="margin-top: 0; margin-bottom: 1rem;">Добавить новое блюдо</h3>
                <form method="POST" action="menu.php" style="display: flex; flex-direction: column; gap: 1rem;">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="name">Название *</label>
                        <input type="text" id="name" name="name" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 0.25rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Категория *</label>
                        <input type="text" id="category" name="category" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 0.25rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Цена (₽) *</label>
                        <input type="number" id="price" name="price" min="0" step="0.01" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 0.25rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Описание</label>
                        <textarea id="description" name="description" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 0.25rem;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="min-width: 160px;"><i class="fas fa-plus"></i> Добавить блюдо</button>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($menu)): ?>
                <div class="admin-menu-list">
                    <?php foreach ($menu as $item): ?>
                        <div class="admin-menu-item" style="border: 1px solid #ddd; border-radius: 0.5rem; margin-bottom: 1rem; overflow: hidden;">
                            <div class="menu-item-header" style="background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-weight: 600; font-size: 1.1rem;"><?= htmlspecialchars($item['name']) ?></div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-secondary" onclick="toggleEdit(<?= $item['id'] ?>)" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="menu.php" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить это блюдо?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-danger" title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="menu-item-content" style="padding: 1rem;">
                                <div class="menu-item-view" id="view-<?= $item['id'] ?>">
                                    <?php if ($item['description']): ?>
                                        <div style="color: #666; margin-bottom: 0.5rem;"><?= nl2br(htmlspecialchars($item['description'])) ?></div>
                                    <?php endif; ?>
                                    <div style="color: #888; font-size: 0.95rem; margin-bottom: 0.5rem;">
                                        <strong>Категория:</strong> <?= htmlspecialchars($item['category']) ?>
                                    </div>
                                    <div style="font-weight: 500; color: #1a8917; font-size: 1.1rem;">
                                        <?= number_format($item['price'], 0, ',', ' ') ?> ₽
                                    </div>
                                </div>
                                
                                <div class="menu-item-edit" id="edit-<?= $item['id'] ?>" style="display: none;">
                                    <form method="POST" action="menu.php">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                            <div class="form-group">
                                                <label for="edit-name-<?= $item['id'] ?>">Название *</label>
                                                <input type="text" id="edit-name-<?= $item['id'] ?>" name="name" value="<?= htmlspecialchars($item['name']) ?>" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 0.25rem;">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="edit-category-<?= $item['id'] ?>">Категория *</label>
                                                <input type="text" id="edit-category-<?= $item['id'] ?>" name="category" value="<?= htmlspecialchars($item['category']) ?>" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 0.25rem;">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="edit-price-<?= $item['id'] ?>">Цена (₽) *</label>
                                                <input type="number" id="edit-price-<?= $item['id'] ?>" name="price" value="<?= $item['price'] ?>" min="0" step="0.01" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 0.25rem;">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="edit-description-<?= $item['id'] ?>">Описание</label>
                                                <textarea id="edit-description-<?= $item['id'] ?>" name="description" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 0.25rem;"><?= htmlspecialchars($item['description']) ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Сохранить
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="toggleEdit(<?= $item['id'] ?>)">
                                                <i class="fas fa-times"></i> Отмена
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #888;">Меню пока пусто. Добавьте первое блюдо!</p>
            <?php endif; ?>
        </div>
    </main>
</div>
<footer class="modern-footer">
    <div class="container">
        <p>&copy; 2024 РесторанБукер. Все права защищены.</p>
    </div>
</footer>
<script>
    function toggleEdit(id) {
        const viewElement = document.getElementById('view-' + id);
        const editElement = document.getElementById('edit-' + id);
        
        if (viewElement.style.display === 'none') {
            viewElement.style.display = 'block';
            editElement.style.display = 'none';
        } else {
            viewElement.style.display = 'none';
            editElement.style.display = 'block';
        }
    }
</script>
</body>
</html> 