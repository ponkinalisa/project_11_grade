<?php
require_once 'main/php/config.php';

session_start();

if (isset($_SESSION['login'])){
   if ($_SESSION['status'] == 'student'){
            header('Location: main/pages/student_main.php');
    }else{
        if ($_SESSION['status'] == 'teacher'){
            header('Location: main/pages/teacher_main.php');
        }
        else{
            header('Location: main/pages/admin.php');
        }
    }
    exit;
}


$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['username']);
    $password = trim($_POST['password']);

    $base_url = 'https://api.gym42.ru/login/';

    $cookie_file = 'cookies.txt';

    $post_data = json_encode([
        'login' => $login,
        'password' => $password
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $base_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($post_data)
        ],
        CURLOPT_COOKIEJAR => $cookie_file, 
        CURLOPT_COOKIEFILE => $cookie_file,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code != 200){
        $error = true;
    }

    curl_close($ch);
    if ($error == false){
        $get_url = 'https://api.gym42.ru/login/'; 

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $get_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_COOKIEFILE => $cookie_file, 
            CURLOPT_COOKIEJAR => $cookie_file, 
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $get_response = json_encode(json_decode(curl_exec($ch), true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $get_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $main = json_decode($get_response, true);

        $i = $main['i'];
        $f = $main['f'];
        $o = $main['o'];
        if (strpos($main['group'], 'чен') != false){
            $status = 'student';
        }else{
            if (strpos($main['group'], 'дмин') != false){
                $status = 'admin';
            }else{
                $status = 'teacher';
            }
        };

        if ($login == 'eaponkina'){
            $status = 'admin';
        }

        $sql = "SELECT * FROM users WHERE login = :login";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user){
            $sql = "INSERT INTO users (login, surname, name, patronymic, status) VALUES (:login, :f, :i, :o, :status)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['login' => $login, 'f' => $f, 'i' => $i, 'o' => $o, 'status' => $status]);

            $sql = "SELECT * FROM users WHERE login = :login";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['login' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $_SESSION['id'] = $user['id'];
            $_SESSION['status'] = $status;
        }else{
            $_SESSION['id'] = $user['id'];
            $_SESSION['status'] = $user['status'];
        }
        $_SESSION['login'] = $login;
        $_SESSION['i'] = $i;
        $_SESSION['f'] = $f;
        $_SESSION['o'] = $o;

        if ($_SESSION['status'] == 'student'){
            header('Location: main/pages/student_main.php');
        }elseif ($status == 'admin'){
            header('Location: main/pages/admin.php');
        }else{
            header('Location: main/pages/teacher_main.php');
        }
        exit;
    }
}


?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система тестирования для школьников</title>
    <link rel="stylesheet" type="text/css" href="main/css/index.css">
</head>
<body>
    <div class="container">

        <div class="welcome-section">
            <h1>Добро пожаловать в систему тестирования МБОУ Гимназии №42</h1>
            <p class="welcome-text">
                Данная система помогает учителям проводить объективное и быстрое оценивание, 
                а ученикам - демонстрировать свои знания в комфортной обстановке.
            </p>
            <p class="welcome-text">
                Желаем успехов в учебе!
            </p>
            
            <div class="features">
                <div class="feature">
                    <span class="feature-check">✓</span>
                    <span>Современный и понятный интерфейс</span>
                </div>
                <div class="feature">
                    <span class="feature-check">✓</span>
                    <span>Точная настройка критериев оценивания и весов заданий</span>
                </div>
                <div class="feature">
                    <span class="feature-check">✓</span>
                    <span>Мгновенные результаты</span>
                </div>
            </div>
        </div>

        <div class="auth-section">
            <h2 class="auth-title">Вход в систему</h2>
            <form id="loginForm" method="post">
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
                
            </form>
        </div>
    </div>

</body>
</html>