<?php
$error = false;
echo($_SERVER['REQUEST_METHOD']);
// Проверяем, был ли отправлен POST-запрос с данными для регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo 1;
}
echo 0;


?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система тестирования для школьников</title>
     <!--<link rel="stylesheet" type="text/css" href="main/css/index.css">-->
</head>
<body>
    <div class="container">
        <!-- Левая часть - приветственный текст -->
        <div class="welcome-section">
            <h1>Добро пожаловать в систему образовательного тестирования</h1>
            <p class="welcome-text">
                Современная платформа для оценки знаний школьников, разработанная с учётом 
                психологических особенностей восприятия и современных образовательных стандартов.
            </p>
            <p class="welcome-text">
                Наша система помогает учителям проводить объективное оценивание, 
                а ученикам - демонстрировать свои знания в комфортной обстановке.
            </p>
            
            <div class="features">
                <div class="feature">
                    <span class="feature-check">✓</span>
                    <span>Современный и понятный интерфейс</span>
                </div>
                <div class="feature">
                    <span class="feature-check">✓</span>
                    <span>Объективная система оценивания</span>
                </div>
                <div class="feature">
                    <span class="feature-check">✓</span>
                    <span>Безопасная среда для тестирования</span>
                </div>
                <div class="feature">
                    <span class="feature-check">✓</span>
                    <span>Мгновенные результаты</span>
                </div>
            </div>
        </div>

        <!-- Правая часть - форма авторизации -->
        <div class="auth-section">
            <h2 class="auth-title">Вход в систему</h2>
            <form id="loginForm" action="i.php" method="post">
                <div class="form-group <?php if ($error){echo('error');} ?>">
                    <label for="username">Логин</label>
                    <input type="text" id="username" name="username" placeholder="Введите ваш логин" required>
                </div>
                
                <div class="form-group <?php if ($error){echo('error');} ?>">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" placeholder="Введите ваш пароль" required>
                </div>

                <?php if ($error){echo('<div class="error-message">Неверный логин или пароль</div>');} ?>
                
                <button type="submit" class="login-btn">Войти в систему</button>
                
                <div class="help-links">
                    <a href="#" class="help-link">Забыли пароль?</a>
                    <a href="#" class="help-link">Помощь</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            // Здесь будет логика авторизации
            console.log('Попытка входа:', { username, password });
        });
    </script>
</body>
</html>