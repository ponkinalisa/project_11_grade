<?php
// Проверяем, что форма отправлена методом POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Получаем данные из формы
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];
    
    // Простая валидация
    if (empty($name) || empty($email) || empty($message)) {
        echo "Все поля обязательны для заполнения!";
        exit;
    }
    
    // Обработка данных
    echo "<h2>Данные получены:</h2>";
    echo "Имя: " . htmlspecialchars($name) . "<br>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
    echo "Сообщение: " . htmlspecialchars($message) . "<br>";
    
    // Здесь можно добавить сохранение в базу данных,
    // отправку email и другую логику
    
} else {
    echo "Форма должна быть отправлена методом POST!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Простая форма</title>
</head>
<body>
    <form action="g.php" method="POST">
        <label for="name">Имя:</label>
        <input type="text" id="name" name="name" required>
        <br><br>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <br><br>
        
        <label for="message">Сообщение:</label>
        <textarea id="message" name="message" required></textarea>
        <br><br>
        
        <input type="submit" value="Отправить">
    </form>
</body>
</html>