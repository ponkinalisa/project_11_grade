<?php 
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) || $_SESSION['status'] == 'student'){
    header('Location: ../../index.php');
    exit;
}

# работа с бд для вывода тестов пользователя

$sql = "SELECT * FROM tests WHERE author_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $_SESSION['id']]);
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель педагога | Образовательная платформа</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" type="text/css" href="../css/teacher_main.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">42</div>
                </div>
                
                <nav class="nav-links">
                    <a href="teacher_main.php" class="nav-link active">Панель управления</a>
                    <a href="teacher_statistics.php" class="nav-link">Статистика</a>
                
                </nav>
                
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo(mb_substr($_SESSION['i'], 0, 1) . mb_substr($_SESSION['f'], 0, 1)); ?></div>
                        <div class="user-name"><?php echo($_SESSION['i'] . ' ' . $_SESSION['f']); ?></div>
                    <a class="test-btn delete-btn" href="../php/logout.php">
                        <span>Выйти</span>
                    </a>
                    </div>
                    <?php
                    if ($_SESSION['status'] == 'admin'){
                        echo('<a class="btn btn-secondary" href="admin.php">
                        <span>Вернуться в админ-панель</span>
                    </a>');
                    }
                    ?>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">

            <div class="page-header">
                <h1>Панель управления педагога</h1>
                <a class="add-test-btn" id="addTestBtn" href="teacher_new_test.php" style="text-decoration: none">
                    <span>+</span>
                    <span>Создать тест</span>
                </a>
            </div>
            
            <div class="tabs">
                <button class="tab active" data-tab="tests">Созданные тесты</button>
            </div>
            
             <div class="tab-content active" id="tests-tab">
                <div class="tests-grid">
             <?php foreach ($tests as $test){
                echo('<div class="test-card"><div class="test-header">');
                if ($test['is_active']){
                    echo('<span class="active-test">Активен</span>');
                }else{
                    echo('<span class="not-active-test">Неактивен</span>');
                }
                echo('<h3 class="test-title">'.$test['name'].'</h3>');
                echo('<div class="test-info"><span>Количество заданий: '.$test['count_tasks'].'</span></div>');
                echo('</div><div class="test-body"><p class="test-description">'.$test['description'].'</p>');
                $sql = "SELECT * FROM test_results WHERE test_id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $test['id']]);
                $test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $count = count($test_results);
                $summa_score = 0;
                $summa_mark = 0;

                foreach ($test_results as $res){
                    $summa_score += round($res['score'] / $test['count_tasks'] * 100);
                    $summa_mark += $res['mark'];
                }
                if ($count){
                    $sredn_score = round($summa_score / $count);
                    $sredn_mark = round($summa_mark / $count);
                }else{
                    $sredn_score = 0;
                    $sredn_mark = 0;
                }

                echo('<div class="test-stats"><div class="test-stat">
                                    <div class="stat-number">'.$count.'</div>
                                    <div class="stat-name">Прошли</div>
                                </div>
                                <div class="test-stat">
                                    <div class="stat-number">'.$sredn_score.'%</div>
                                    <div class="stat-name">Средний балл</div>
                                </div>
                                <div class="test-stat">
                                    <div class="stat-number">'.$sredn_mark.'</div>
                                    <div class="stat-name">Средняя оценка</div>
                                </div>
                            </div>
                ');
                echo('</div>
                        <div class="test-footer">
                            <a href="teacher_edit_test.php?test_id='.$test['id'].'" class="test-btn edit-btn">Редактировать</a>
                            <a href="teacher_test_results.php?test_id='.$test['id'].'" class="test-btn results-btn">Результаты</a>
                            <a href="../php/delete_test.php?test_id='.$test['id'].'" class="test-btn delete-btn">Удалить</a>
                        </div>
                    </div>');
             }?>
            
            <div class="tab-content" id="statistics-tab">
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-value">8</div>
                        <div class="stat-label">Созданных тестов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">145</div>
                        <div class="stat-label">Всего прохождений</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">79%</div>
                        <div class="stat-label">Средний результат</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">92%</div>
                        <div class="stat-label">Активность учеников</div>
                    </div>
                </div>
                
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="card-header">
                            <h2 class="card-title">Результаты тестов по предметам</h2>
                        </div>
                        <div class="chart-container">
                            <canvas id="subjectChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="card-header">
                            <h2 class="card-title">Активность по дням</h2>
                        </div>
                        <div class="chart-container">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tab-content" id="classes-tab">
                <div class="empty-state">
                    <div class="empty-icon">🏫</div>
                    <h3>Управление классами</h3>
                    <p>Здесь вы можете управлять своими классами и назначать тесты</p>
                    <button class="add-test-btn" style="margin-top: 20px;">
                        <span>+</span>
                        <span>Добавить класс</span>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                 <div class="copyright">
                    © 2025 МБОУ Гимназия №42 Алтайского края. Все права защищены.
                </div>
                <div class="footer-links">
                    <a href="https://gymn42.gosuslugi.ru/" class="footer-link">Сайт Гимназии</a>
                    <a href="tel:+73852226810" class="footer-link">Контакты</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                tab.classList.add('active');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
        
        
        
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        const subjectChart = new Chart(subjectCtx, {
            type: 'bar',
            data: {
                labels: ['Математика', 'История', 'Биология', 'Физика', 'Литература'],
                datasets: [{
                    label: 'Средний балл, %',
                    data: [78, 82, 75, 85, 72],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(236, 72, 153, 0.7)'
                    ],
                    borderColor: [
                        '#3B82F6',
                        '#10B981',
                        '#F59E0B',
                        '#8B5CF6',
                        '#EC4899'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                datasets: [{
                    label: 'Прохождения тестов',
                    data: [12, 19, 15, 22, 18, 5, 3],
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>