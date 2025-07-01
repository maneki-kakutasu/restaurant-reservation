<?php
    session_start();
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';

    $errors = [];
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Валидация
        if (empty($email)) {
            $errors[] = 'Email обязателен';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный формат email';
        }
        
        if (empty($password)) {
            $errors[] = 'Пароль обязателен';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Пароль должен содержать минимум 6 символов';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Пароли не совпадают';
        }
        
        if (empty($name)) {
            $errors[] = 'Имя обязательно';
        }
        
        // Проверка существования email
        if (empty($errors)) {
            $sql = "SELECT id FROM users WHERE email = ?";
            $stmt = executeQuery($sql, [$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Пользователь с таким email уже существует';
            }
        }
        
        // Регистрация
        if (empty($errors)) {
            if (registerUser($email, $password, $name, $phone)) {
                $success = true;
            } else {
                $errors[] = 'Ошибка при регистрации. Попробуйте еще раз.';
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
    <title>Регистрация - РесторанБукер</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <main>
        <div class="container centered-auth">
            <div class="auth-form">
                <h2>Регистрация</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p>Регистрация успешно завершена! Теперь вы можете <a href="login.php">войти в систему</a>.</p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="register.php">
                        <div class="form-group">
                            <label for="name">Имя *</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required placeholder="Ваше имя">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required placeholder="example@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Телефон</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+7 (___) ___-__-__">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Пароль *</label>
                            <input type="password" id="password" name="password" required placeholder="Минимум 6 символов">
                            <small>Минимум 6 символов</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Подтвердите пароль *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Повторите пароль">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
                    </form>
                    
                    <div class="auth-links">
                        <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html> 