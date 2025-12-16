<?php
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) || $_SESSION['status'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

try {
    $sql = "SELECT 
        (SELECT COUNT(*) FROM users WHERE status = 'student') as total_students,
        (SELECT COUNT(*) FROM users WHERE status = 'teacher') as total_teachers,
        (SELECT COUNT(*) FROM tests) as total_tests,
        (SELECT COUNT(*) FROM test_results) as total_results";
    $stmt = $pdo->query($sql);
    $system_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT 
        DATE(date) as day,
        COUNT(*) as tests_taken
        FROM test_results 
        WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(date)
        ORDER BY day DESC";
    $stmt = $pdo->query($sql);
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Ошибка при получении статистики: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора | Образовательная платформа</title>
    <link rel="stylesheet" type="text/css" href="../css/index.css">
    <link rel="stylesheet" type="text/css" href="../css/admin.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            justify-self: center;
            text-align: center;
        }
        
        .admin-header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .admin-actions {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Панель администратора</h1>
        </div>
        
        <div class="admin-panel">
            <div class="choose-role">
                <a href="student_main.php" class="btn btn-primary">Войти как ученик</a>
                <a href="teacher_main.php" class="btn btn-primary">Войти как учитель</a>
            </div>
            <h2>Статистика системы</h2>
            <div class="admin-statistics">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $system_stats['total_students']; ?></div>
                    <div class="stat-label">Учеников</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $system_stats['total_teachers']; ?></div>
                    <div class="stat-label">Преподавателей</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $system_stats['total_tests']; ?></div>
                    <div class="stat-label">Тестов</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $system_stats['total_results']; ?></div>
                    <div class="stat-label">Результатов</div>
                </div>
            </div>
        </div>

        <div class="admin-actions">
            <a href="../php.logout.php" class="btn btn-secondary">
                Выйти
            </a>
        </div>
        
    </div>
</body>
</html>