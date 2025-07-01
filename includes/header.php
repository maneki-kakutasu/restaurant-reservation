<?php
// ... existing code ...
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Reservation</title>
    <link rel="stylesheet" href="/assets/css/header.css">
    <!-- Подключение других стилей, шрифтов и т.д. -->
</head>
<body>
  <header>
      <nav class="navbar modern-navbar">
        <div class="nav-container">
          <div class="nav-menu" id="navMenu">
            <a href="index.php" class="nav-link active">Главная</a>
            <?php if (isset($_SESSION['user_id'])): ?>
              <a href="profile.php" class="nav-link">Профиль</a>
              <a href="logout.php" class="nav-link">Выйти</a>
            <?php else: ?>
              <a href="login.php" class="nav-link">Войти</a>
              <a href="register.php" class="nav-link">Регистрация</a>
            <?php endif; ?>
          </div>
          <div class="burger" id="burgerMenu">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
      </nav>
    </header>

</body>
</html> 