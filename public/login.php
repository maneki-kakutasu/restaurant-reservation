<?php
    session_start();
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';

    $errors = [];

    // Если пользователь уже авторизован, перенаправляем на главную
    if (isLoggedIn()) {
        header('Location: index.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Валидация
        if (empty($email)) {
            $errors[] = 'Email обязателен';
        }
        
        if (empty($password)) {
            $errors[] = 'Пароль обязателен';
        }
        
        // Авторизация
        if (empty($errors)) {
            $user = loginUser($email, $password);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Перенаправление в зависимости от роли
                if ($user['role'] === 'restaurant_admin') {
                    header('Location: admin/dashboard.php');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $errors[] = 'Неверный email или пароль';
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
    <title>Вход - РесторанБукер</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <main>
        <div class="container centered-auth">
            <div class="auth-form">
                <h2>Вход в систему</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required placeholder="example@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" required placeholder="Ваш пароль">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Войти</button>
                </form>
                
                <div class="auth-links">
                    <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                </div>
                
                <div class="demo-accounts">
                    <h3>Демо-аккаунты:</h3>
                    <div class="demo-account">
                        <strong>Обычный пользователь:</strong><br>
                        Email: user@example.com<br>
                        Пароль: password
                    </div>
                    <div class="demo-account">
                        <strong>Администратор ресторана:</strong><br>
                        Email: restaurant1@example.com<br>
                        Пароль: password
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html> 