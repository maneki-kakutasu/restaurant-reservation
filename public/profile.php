<?php
    session_start();
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';

    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    // Если админ ресторана, перенаправляем на админ-панель
    if ($_SESSION['user_role'] === 'restaurant_admin') {
        header('Location: admin/dashboard.php');
        exit;
    }

    $user = getUserById($_SESSION['user_id']);
    $edit_errors = [];
    $edit_success = false;
    $cancel_success = false;
    $cancel_error = '';

    // Обработка редактирования бронирования
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_booking_id'])) {
        $booking_id = (int)$_POST['edit_booking_id'];
        $date = trim($_POST['edit_date'] ?? '');
        $time = trim($_POST['edit_time'] ?? '');
        $guests = (int)($_POST['edit_guests'] ?? 0);
        $special_requests = trim($_POST['edit_special_requests'] ?? '');

        // Валидация
        if (empty($date)) {
            $edit_errors[] = 'Выберите дату';
        } elseif (!validateDate($date)) {
            $edit_errors[] = 'Некорректная дата';
        } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
            $edit_errors[] = 'Нельзя бронировать на прошедшую дату';
        }

        if (empty($time)) {
            $edit_errors[] = 'Выберите время';
        } elseif (!validateTime($time)) {
            $edit_errors[] = 'Некорректное время';
        }
        
        if ($guests < 1 || $guests > 20) {
            $edit_errors[] = 'Количество гостей должно быть от 1 до 20';
        }

        if (empty($edit_errors)) {
            if (updateBooking($booking_id, $_SESSION['user_id'], $date, $time, $guests, $special_requests)) {
                $edit_success = true;
            } else {
                $edit_errors[] = 'Ошибка при обновлении бронирования';
            }
        }
    }

    // Обработка отмены бронирования
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
        $cancel_id = (int)$_POST['cancel_booking_id'];
        if (cancelBooking($cancel_id, $_SESSION['user_id'])) {
            $cancel_success = true;
        } else {
            $cancel_error = 'Не удалось отменить бронирование.';
        }
    }

    $bookings = getUserBookings($_SESSION['user_id']);

    // Сортировка: активные слева, затем отменённые/завершённые, внутри групп — по дате убыв.
    usort($bookings, function($a, $b) {
        $a_active = ($a['status'] !== 'cancelled' && $a['status'] !== 'completed');
        $b_active = ($b['status'] !== 'cancelled' && $b['status'] !== 'completed');
        if ($a_active !== $b_active) return $a_active ? -1 : 1;
        // Сортировка по дате убыв.
        $dateA = $a['booking_date'] . ' ' . $a['booking_time'];
        $dateB = $b['booking_date'] . ' ' . $b['booking_time'];
        return strcmp($dateB, $dateA);
    });

    include __DIR__ . '/../includes/header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя - РесторанБукер</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .modal-edit { display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); align-items:center; justify-content:center; }
        .modal-edit.active { display:flex; }
        .modal-content-edit { background:#fff; border-radius:16px; padding:2rem 1.5rem; max-width:350px; width:100%; box-shadow:0 8px 32px rgba(44,62,80,0.16); position:relative; }
        .modal-content-edit .close { position:absolute; right:1rem; top:1rem; font-size:1.5rem; color:#888; cursor:pointer; }
    </style>
</head>
<body>
    <main>
        <div class="container centered-auth">
            <div class="auth-form profile-form">
                <h2><i class="fas fa-user-circle"></i> Профиль</h2>
                <div class="profile-info">
                    <p><strong>Имя:</strong> <?= htmlspecialchars($user['name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <?php if (!empty($user['phone'])): ?>
                        <p><strong>Телефон:</strong> <?= htmlspecialchars($user['phone']) ?></p>
                    <?php endif; ?>
                    <p><strong>Дата регистрации:</strong> <?= formatDate($user['created_at']) ?></p>
                </div>
                <a href="logout.php" class="btn btn-secondary btn-block" style="margin-top:1.5rem;">Выйти из аккаунта</a>
            </div>
        </div>
        <div class="container" style="margin-top:2rem;">
            <div class="profile-bookings">
                <h2 style="text-align:center;"><i class="fas fa-calendar-check"></i> Мои бронирования</h2>
                <?php if ($cancel_success): ?>
                    <div class="alert alert-success">Бронирование успешно отменено!</div>
                <?php elseif ($cancel_error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($cancel_error) ?></div>
                <?php endif; ?>
                <?php if ($edit_success): ?>
                    <div class="alert alert-success">Бронирование успешно обновлено!</div>
                <?php elseif (!empty($edit_errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($edit_errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (empty($bookings)): ?>
                    <div class="no-results" style="margin:2rem 0;">У вас пока нет бронирований.</div>
                <?php else: ?>
                    <div class="bookings-scroll"><div class="bookings-list">
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                                $is_cancelled = $booking['status'] === 'cancelled';
                                $is_completed = $booking['status'] === 'completed';
                                $card_class = 'modern-card';
                                if ($is_cancelled) $card_class .= ' booking-cancelled';
                                if ($is_completed) $card_class .= ' booking-completed';
                            ?>
                            <div class="<?= $card_class ?> booking-scroll-card" style="max-width:400px;position:relative;">
                                <div class="restaurant-info" style="align-items:flex-start;text-align:left;">
                                    <h3><i class="fas fa-utensils"></i> <?= htmlspecialchars($booking['restaurant_name']) ?></h3>
                                    <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($booking['restaurant_address']) ?></p>
                                    <p><i class="fas fa-calendar"></i> <?= formatDate($booking['booking_date']) ?> &nbsp; <i class="fas fa-clock"></i> <?= formatTime($booking['booking_time']) ?></p>
                                    <p><i class="fas fa-users"></i> Гостей: <?= (int)$booking['guests'] ?></p>
                                    <p><strong>Статус:</strong> <?= htmlspecialchars(getBookingStatusText($booking['status'])) ?></p>
                                    <?php if (!empty($booking['special_requests'])): ?>
                                        <p><i class="fas fa-sticky-note"></i> <?= htmlspecialchars($booking['special_requests']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($is_cancelled): ?>
                                        <div class="booking-status-label cancelled"><i class="fas fa-ban"></i> Отменено</div>
                                    <?php elseif ($is_completed): ?>
                                        <div class="booking-status-label completed"><i class="fas fa-check-circle"></i> Завершено</div>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" style="margin-top:0.7rem;" onclick="openEditModal(<?= $booking['id'] ?>, '<?= $booking['booking_date'] ?>', '<?= $booking['booking_time'] ?>', <?= (int)$booking['guests'] ?>, `<?= htmlspecialchars($booking['special_requests'], ENT_QUOTES) ?>`)" type="button"><i class="fas fa-edit"></i> Редактировать</button>
                                        <form method="POST" action="profile.php" style="display:inline;">
                                            <input type="hidden" name="cancel_booking_id" value="<?= $booking['id'] ?>">
                                            <button type="submit" class="btn btn-block btn-secondary" style="margin-top:0.5rem;background:#eee;color:#c0392b;border:1px solid #c0392b;">Отменить</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div></div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Модальное окно для редактирования -->
        <div class="modal-edit" id="editModal">
            <div class="modal-content-edit">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h3 style="text-align:center;"><i class="fas fa-edit"></i> Редактировать бронь</h3>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="edit_booking_id" id="edit_booking_id">
                    <div class="form-group">
                        <label for="edit_date">Дата</label>
                        <input type="date" name="edit_date" id="edit_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_time">Время</label>
                        <input type="time" name="edit_time" id="edit_time" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_guests">Гостей</label>
                        <input type="number" name="edit_guests" id="edit_guests" min="1" max="20" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_special_requests">Пожелания</label>
                        <input type="text" name="edit_special_requests" id="edit_special_requests" placeholder="(необязательно)">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Сохранить изменения</button>
                </form>
            </div>
        </div>
    </main>
    <!-- Модальное окно для редактирования -->
    <div class="modal-edit" id="editModal">
        <div class="modal-content-edit">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3 style="text-align:center;"><i class="fas fa-edit"></i> Редактировать бронь</h3>
            <form method="POST" action="profile.php">
                <input type="hidden" name="edit_booking_id" id="edit_booking_id">
                <div class="form-group">
                    <label for="edit_date">Дата</label>
                    <input type="date" name="edit_date" id="edit_date" required>
                </div>
                <div class="form-group">
                    <label for="edit_time">Время</label>
                    <input type="time" name="edit_time" id="edit_time" required>
                </div>
                <div class="form-group">
                    <label for="edit_guests">Гостей</label>
                    <input type="number" name="edit_guests" id="edit_guests" min="1" max="20" required>
                </div>
                <div class="form-group">
                    <label for="edit_special_requests">Пожелания</label>
                    <input type="text" name="edit_special_requests" id="edit_special_requests" placeholder="(необязательно)">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Сохранить изменения</button>
            </form>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    ?>
    <script src="assets/js/script.js"></script>
    <script>
        // Burger menu for mobile
        const burger = document.getElementById('burgerMenu');
        const navMenu = document.getElementById('navMenu');
        burger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            burger.classList.toggle('active');
        });

        // Модальное окно редактирования
        function openEditModal(id, date, time, guests, special) {
            document.getElementById('edit_booking_id').value = id;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_time').value = time;
            document.getElementById('edit_guests').value = guests;
            document.getElementById('edit_special_requests').value = special || '';
            document.getElementById('editModal').classList.add('active');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        // Закрытие по клику вне окна
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) closeEditModal();
        }
    </script>
</body>
</html> 