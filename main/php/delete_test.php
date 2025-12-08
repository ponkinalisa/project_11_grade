<?php
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) || $_SESSION['status'] == 'student') {
    header('Location: ../../index.php');
    exit;
}

$test_id = $_GET['test_id'] ?? null;

if (!$test_id) {
    die("Тест не указан");
}

// Проверяем, принадлежит ли тест текущему учителю
try {
    $sql = "SELECT author_id FROM tests WHERE id = :test_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['test_id' => $test_id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test || $test['author_id'] != $_SESSION['id']) {
        die("У вас нет прав для удаления этого теста");
    }
} catch (PDOException $e) {
    die("Ошибка при проверке прав: " . $e->getMessage());
}

// Если пользователь подтвердил удаление
if (isset($_POST['confirm_delete'])) {
    try {
$sql = "SELECT path_to_img FROM tasks WHERE test_id = :test_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['test_id' => $test_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($files as $file){
    if (file_exists($file['path_to_img'])){
        unlink($file['path_to_img']);
    }
}

$sql = "DELETE FROM tasks WHERE test_id = :test_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['test_id' => $test_id]);

$sql = "DELETE FROM types WHERE test_id = :test_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['test_id' => $test_id]);

$sql = "DELETE FROM test_results WHERE test_id = :test_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['test_id' => $test_id]);

$sql = "DELETE FROM tests WHERE id = :test_id AND author_id = :author_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['test_id' => $test_id, 'author_id' => $_SESSION['id']]);
        
        header('Location: ../pages/teacher_main.php');
        exit;
        
    } catch (PDOException $e) {
        die("Ошибка при удалении теста: " . $e->getMessage());
    }
}

// Показываем страницу подтверждения
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение удаления | Образовательная платформа</title>
    <link rel="stylesheet" type="text/css" href="../css/teacher_main.css">
    <style>
        .confirmation-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .warning-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .confirmation-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="warning-icon">⚠️</div>
        <h2>Подтверждение удаления</h2>
        <p>Вы уверены, что хотите удалить этот тест?</p>
        <p><strong>Это действие нельзя отменить!</strong></p>
        <p>Все связанные данные (результаты, задания) будут удалены.</p>
        
        <div class="confirmation-buttons">
            <form method="post" style="display: inline;">
                <button type="submit" name="confirm_delete" class="btn btn-danger">
                    Да, удалить тест
                </button>
            </form>
            <a href="../pages/teacher_main.php" class="btn btn-secondary">
                Нет, отменить
            </a>
        </div>
    </div>
</body>
</html>