<?php
  session_start();

  // Очищаем все данные сессии
  session_destroy();

  // Перенаправляем на главную страницу
  header('Location: index.php');
exit; 