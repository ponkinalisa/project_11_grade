<?php
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) and $_SESSION['status'] != 'teacher'){
    header('Location: ../../index.php');
    exit;
}

$attempt_id = $_GET['attempt_id'] ?? null;
$result_data = null;
$test_info = null;
$answers_data = [];

if ($attempt_id) {
    try {
        $sql = "SELECT * FROM test_results WHERE test_id = :attempt_id AND student_id = :student_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['attempt_id' => $attempt_id, 'student_id' => $_SESSION['id']]);
        $result_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $sql = "SELECT * FROM tests WHERE id = :attempt_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['attempt_id' => $attempt_id]);
        $test = $stmt->fetch(PDO::FETCH_ASSOC);

        $author_id = $test['author_id'];
        $sql = "SELECT * FROM users WHERE id = :author_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['author_id' => $author_id]);
        $author = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result_data) {
            die("Результат не найден");
        }
        
    } catch (PDOException $e) {
        echo 'Ошибка при загрузке результатов: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты теста | Образовательная платформа</title>
    <link rel="stylesheet" type="text/css" href="../css/new_test.css">
    <link rel="stylesheet" type="text/css" href="../css/student_test_results.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">42</div>
                </div>
                
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo(mb_substr($_SESSION['i'], 0, 1) . mb_substr($_SESSION['f'], 0, 1)); ?></div>
                        <div class="user-name"><?php echo($_SESSION['i'] . ' ' . $_SESSION['f']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php if (!$result_data): ?>
                <div class="result-container">
                    <div class="empty-state">
                        <div class="empty-state-icon">📊</div>
                        <h3>Результат не найден</h3>
                        <p>Запрошенный результат тестирования не существует или у вас нет к нему доступа.</p>
                        <a href="student_tests.php" class="btn btn-primary">Вернуться к тестам</a>
                    </div>
                </div>
            <?php else: 
                $percentage = ($result_data['score'] / $test['count_tasks']) * 100;
                if ($percentage >= $test['grade5']) {
                    $grade = '5';
                    $grade_class = 'score-excellent';
                    $grade_label = 'Отлично';
                } elseif ($percentage >= $test['grade4']) {
                    $grade = '4';
                    $grade_class = 'score-good';
                    $grade_label = 'Хорошо';
                } elseif ($percentage >= $test['grade3']) {
                    $grade = '3';
                    $grade_class = 'score-satisfactory';
                    $grade_label = 'Удовлетворительно';
                } else {
                    $grade = '2';
                    $grade_class = 'score-poor';
                    $grade_label = 'Неудовлетворительно';
                }
            ?>
                <div class="result-container">
                    <div class="result-header">
                        <h1>Результаты теста</h1>
                        <h2><?php echo htmlspecialchars($test['name']); ?></h2>
                        <p style="color: var(--text-secondary); margin-top: 10px;">
                            Преподаватель: <?php echo htmlspecialchars($author['name'] . ' ' . $author['patronymic']. ' ' . $author['surname']); ?>
                        </p>
                    </div>
                    
                    <div class="result-score <?php echo $grade_class; ?>">
                        <?php echo $result_data['score']; ?>/<?php echo $test['count_tasks']; ?>
                    </div>
                    
                    <div style="font-size: 1.5rem; margin-bottom: 10px; font-weight: 600;" class="<?php echo $grade_class; ?>">
                        Оценка: <?php echo $grade; ?> (<?php echo $grade_label; ?>)
                    </div>
                    
                    <div style="font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 30px;">
                        Выполнено на <?php echo round($percentage, 1); ?>%
                    </div>
                    
                    <div class="progress-circle">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle class="circle-bg" cx="60" cy="60" r="54"></circle>
                            <circle class="circle-progress" cx="60" cy="60" r="54" 
                                    stroke-dasharray="339.292" 
                                    stroke-dashoffset="<?php echo 339.292 * (1 - $percentage / 100); ?>"></circle>
                            <text x="60" y="60" text-anchor="middle" dy="0" class="circle-text">
                                <?php echo round($percentage); ?>%
                            </text>
                            <text x="60" y="75" text-anchor="middle" dy="0" class="circle-label">
                                выполнения
                            </text>
                        </svg>
                    </div>
                    
                    <div class="result-details">
                        <div class="detail-card">
                            <div class="detail-value"><?php echo $result_data['score']; ?></div>
                            <div class="detail-label">Правильных ответов</div>
                        </div>
                        <div class="detail-card">
                            <div class="detail-value"><?php echo $test['count_tasks'] - $result_data['score']; ?></div>
                            <div class="detail-label">Неправильных ответов</div>
                        </div>
                        <div class="detail-card">
                            <div class="detail-value"><?php echo round($percentage, 1); ?>%</div>
                            <div class="detail-label">Процент выполнения</div>
                        </div>
                        <div class="detail-card">
                            <div class="detail-value"><?php echo date('d.m.Y', strtotime($result_data['date'])); ?></div>
                            <div class="detail-label">Дата прохождения</div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="student_main.php" class="btn btn-outline">Вернуться к списку тестов</a>
                    </div>
                </div>
                
                <div class="result-container">
                    <h3>
                        <?php if ($percentage >= 90): ?>
                            🎉 Превосходно! Вы показали выдающийся результат!
                        <?php elseif ($percentage >= 75): ?>
                            👍 Отличная работа! Вы хорошо усвоили материал!
                        <?php elseif ($percentage >= 60): ?>
                            📚 Хороший результат! Есть куда стремиться!
                        <?php else: ?>
                            💪 Не отчаивайтесь! Повторите материал и попробуйте снова!
                        <?php endif; ?>
                    </h3>
                    <p style="color: var(--text-secondary); margin-top: 10px;">
                        <?php if ($percentage >= 90): ?>
                            Ваши знания на высшем уровне! Продолжайте в том же духе!
                        <?php elseif ($percentage >= 75): ?>
                            Вы демонстрируете уверенное владение материалом.
                        <?php elseif ($percentage >= 60): ?>
                            Рекомендуем повторить данную тему и до конца разобраться в ней.
                        <?php else: ?>
                            Рекомендуем тщательно изучить материал и пройти тест повторно.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const progressCircle = document.querySelector('.circle-progress');
            
            const elements = document.querySelectorAll('.result-container > *');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'all 0.6s ease';
                
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
        
    </script>
</body>
</html>