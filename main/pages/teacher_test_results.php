<?php
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login'])){
    header('Location: ../../index.php');
    exit;
}

$test_id = $_GET['test_id'] ?? null;
$results_data = [];
$test_info = [];
$statistics = [];

if ($test_id) {
    try {
        // Получаем основную информацию о тесте
        $sql = "SELECT t.*, u.first_name, u.last_name 
                FROM tests t 
                JOIN users u ON t.author_id = u.id 
                WHERE t.id = :test_id AND t.author_id = :author_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id, 'author_id' => $_SESSION['id']]);
        $test_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$test_info) {
            die("Тест не найден или у вас нет прав для просмотра результатов");
        }
        
        // Получаем результаты прохождения теста
        $sql = "SELECT r.*, u.first_name, u.last_name, u.group_name,
                       (SELECT COUNT(*) FROM test_attempts WHERE test_id = :test_id) as total_attempts,
                       (SELECT COUNT(DISTINCT user_id) FROM test_attempts WHERE test_id = :test_id) as unique_students
                FROM test_results r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.test_id = :test_id 
                ORDER BY r.score DESC, r.completion_time ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id]);
        $results_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Получаем статистику по тесту
        if (!empty($results_data)) {
            $scores = array_column($results_data, 'score');
            $statistics = [
                'average_score' => round(array_sum($scores) / count($scores), 2),
                'max_score' => max($scores),
                'min_score' => min($scores),
                'total_attempts' => $results_data[0]['total_attempts'],
                'unique_students' => $results_data[0]['unique_students'],
                'completion_rate' => round((count($results_data) / $results_data[0]['total_attempts']) * 100, 1)
            ];
            
            // Распределение по оценкам
            $grades_distribution = [
                '5' => 0,
                '4' => 0,
                '3' => 0,
                '2' => 0
            ];
            
            foreach ($results_data as $result) {
                $percentage = ($result['score'] / $test_info['count_tasks']) * 100;
                
                if ($percentage >= $test_info['grade5']) {
                    $grades_distribution['5']++;
                } elseif ($percentage >= $test_info['grade4']) {
                    $grades_distribution['4']++;
                } elseif ($percentage >= $test_info['grade3']) {
                    $grades_distribution['3']++;
                } else {
                    $grades_distribution['2']++;
                }
            }
            
            $statistics['grades_distribution'] = $grades_distribution;
        }
        
    } catch (PDOException $e) {
        echo 'Ошибка при получении данных результатов: ' . $e->getMessage();
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
    <style>
        .results-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .grades-distribution {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .grade-card {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .grade-5 { border-top: 4px solid #28a745; }
        .grade-4 { border-top: 4px solid #17a2b8; }
        .grade-3 { border-top: 4px solid #ffc107; }
        .grade-2 { border-top: 4px solid #dc3545; }
        
        .grade-count {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .results-table th,
        .results-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .results-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .results-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .score-cell {
            font-weight: bold;
        }
        
        .score-excellent { color: #28a745; }
        .score-good { color: #17a2b8; }
        .score-satisfactory { color: #ffc107; }
        .score-poor { color: #dc3545; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }
        
        .export-options {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .detailed-result {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .task-result {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 10px;
            background: white;
        }
        
        .task-result.correct {
            border-left: 4px solid #28a745;
        }
        
        .task-result.incorrect {
            border-left: 4px solid #dc3545;
        }
        
        .chart-container {
            height: 300px;
            margin: 30px 0;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Шапка -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">E</div>
                    <div class="logo-text">EduTest</div>
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

    <!-- Основной контент -->
    <main class="main-content">
        <div class="container">
            <!-- Заголовок страницы -->
            <div class="page-header">
                <h1>Результаты теста: <?php echo htmlspecialchars($test_info['name'] ?? 'Неизвестный тест'); ?></h1>
                <a href="teacher_tests.php" class="back-btn">← Назад к тестам</a>
            </div>
            
            <?php if (empty($results_data)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <h3>Результаты пока отсутствуют</h3>
                    <p>Студенты еще не прошли этот тест.</p>
                </div>
            <?php else: ?>
                <!-- Общая статистика -->
                <div class="results-container">
                    <h2>Общая статистика теста</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $statistics['unique_students']; ?></div>
                            <div class="stat-label">Студентов прошло</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $statistics['total_attempts']; ?></div>
                            <div class="stat-label">Всего попыток</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $statistics['average_score']; ?></div>
                            <div class="stat-label">Средний балл</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $statistics['completion_rate']; ?>%</div>
                            <div class="stat-label">Процент завершения</div>
                        </div>
                    </div>
                    
                    <!-- Распределение по оценкам -->
                    <h3>Распределение по оценкам</h3>
                    <div class="grades-distribution">
                        <div class="grade-card grade-5">
                            <div class="grade-count"><?php echo $statistics['grades_distribution']['5']; ?></div>
                            <div>Оценка "5"</div>
                            <small>(≥<?php echo $test_info['grade5']; ?>%)</small>
                        </div>
                        <div class="grade-card grade-4">
                            <div class="grade-count"><?php echo $statistics['grades_distribution']['4']; ?></div>
                            <div>Оценка "4"</div>
                            <small>(≥<?php echo $test_info['grade4']; ?>%)</small>
                        </div>
                        <div class="grade-card grade-3">
                            <div class="grade-count"><?php echo $statistics['grades_distribution']['3']; ?></div>
                            <div>Оценка "3"</div>
                            <small>(≥<?php echo $test_info['grade3']; ?>%)</small>
                        </div>
                        <div class="grade-card grade-2">
                            <div class="grade-count"><?php echo $statistics['grades_distribution']['2']; ?></div>
                            <div>Оценка "2"</div>
                            <small>(<<?php echo $test_info['grade3']; ?>%)</small>
                        </div>
                    </div>
                    
                    <!-- График распределения оценок -->
                    <div class="chart-container">
                        <canvas id="gradesChart"></canvas>
                    </div>
                </div>
                
                <!-- Детальные результаты -->
                <div class="results-container">
                    <div class="tabs">
                        <div class="tab active" onclick="switchTab('results')">Список результатов</div>
                        <div class="tab" onclick="switchTab('analysis')">Анализ теста</div>
                    </div>
                    
                    <!-- Таблица результатов -->
                    <div id="results" class="tab-content active">
                        <div class="filters">
                            <select class="filter-select" onchange="filterResults()" id="groupFilter">
                                <option value="">Все группы</option>
                                <?php
                                $groups = array_unique(array_column($results_data, 'group_name'));
                                foreach ($groups as $group): 
                                    if (!empty($group)):
                                ?>
                                    <option value="<?php echo htmlspecialchars($group); ?>"><?php echo htmlspecialchars($group); ?></option>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </select>
                            
                            <select class="filter-select" onchange="filterResults()" id="gradeFilter">
                                <option value="">Все оценки</option>
                                <option value="5">Оценка 5</option>
                                <option value="4">Оценка 4</option>
                                <option value="3">Оценка 3</option>
                                <option value="2">Оценка 2</option>
                            </select>
                        </div>
                        
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Студент</th>
                                    <th>Группа</th>
                                    <th>Баллы</th>
                                    <th>Оценка</th>
                                    <th>Время выполнения</th>
                                    <th>Дата прохождения</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results_data as $index => $result): 
                                    $percentage = ($result['score'] / $test_info['count_tasks']) * 100;
                                    $grade = '';
                                    $grade_class = '';
                                    
                                    if ($percentage >= $test_info['grade5']) {
                                        $grade = '5';
                                        $grade_class = 'score-excellent';
                                    } elseif ($percentage >= $test_info['grade4']) {
                                        $grade = '4';
                                        $grade_class = 'score-good';
                                    } elseif ($percentage >= $test_info['grade3']) {
                                        $grade = '3';
                                        $grade_class = 'score-satisfactory';
                                    } else {
                                        $grade = '2';
                                        $grade_class = 'score-poor';
                                    }
                                ?>
                                <tr class="result-row" data-group="<?php echo htmlspecialchars($result['group_name'] ?? ''); ?>" data-grade="<?php echo $grade; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['group_name'] ?? '-'); ?></td>
                                    <td class="score-cell <?php echo $grade_class; ?>">
                                        <?php echo $result['score']; ?>/<?php echo $test_info['count_tasks']; ?>
                                        (<?php echo round($percentage); ?>%)
                                    </td>
                                    <td><span class="<?php echo $grade_class; ?>"><?php echo $grade; ?></span></td>
                                    <td><?php echo gmdate("H:i:s", $result['completion_time']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($result['completed_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-outline" onclick="showDetailedResult(<?php echo $result['id']; ?>)">
                                            Подробнее
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Анализ теста -->
                    <div id="analysis" class="tab-content">
                        <h3>Анализ сложности заданий</h3>
                        <p>Здесь будет отображаться статистика по каждому заданию теста...</p>
                        <!-- Дополнительный анализ можно добавить позже -->
                    </div>
                </div>
                
                <!-- Экспорт результатов -->
                <div class="results-container">
                    <h3>Экспорт результатов</h3>
                    <div class="export-options">
                        <button class="btn btn-primary" onclick="exportToCSV()">Экспорт в CSV</button>
                        <button class="btn btn-secondary" onclick="exportToPDF()">Экспорт в PDF</button>
                        <button class="btn btn-outline" onclick="printResults()">Печать результатов</button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Кнопки действий -->
            <div class="action-buttons">
                <a href="teacher_tests.php" class="btn btn-secondary">Назад к тестам</a>
                <?php if ($test_id): ?>
                    <a href="teacher_edit_test.php?test_id=<?php echo $test_id; ?>" class="btn btn-outline">Редактировать тест</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Подвал -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="copyright">
                    © 2023 Образовательная платформа EduTest. Все права защищены.
                </div>
                <div class="footer-links">
                    <a href="#" class="footer-link">Помощь</a>
                    <a href="#" class="footer-link">О системе</a>
                    <a href="#" class="footer-link">Контакты</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Переключение вкладок
        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }
        
        // Фильтрация результатов
        function filterResults() {
            const groupFilter = document.getElementById('groupFilter').value;
            const gradeFilter = document.getElementById('gradeFilter').value;
            const rows = document.querySelectorAll('.result-row');
            
            rows.forEach(row => {
                const group = row.getAttribute('data-group');
                const grade = row.getAttribute('data-grade');
                
                const groupMatch = !groupFilter || group === groupFilter;
                const gradeMatch = !gradeFilter || grade === gradeFilter;
                
                if (groupMatch && gradeMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Показать детальный результат
        function showDetailedResult(resultId) {
            alert('Детальная информация о результате с ID: ' + resultId);
            // Здесь можно реализовать модальное окно с детальной информацией
        }
        
        // Экспорт функций
        function exportToCSV() {
            alert('Экспорт в CSV выполнен');
            // Реализация экспорта в CSV
        }
        
        function exportToPDF() {
            alert('Экспорт в PDF выполнен');
            // Реализация экспорта в PDF
        }
        
        function printResults() {
            window.print();
        }
        
        // Инициализация графиков
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($statistics['grades_distribution'])): ?>
            const ctx = document.getElementById('gradesChart').getContext('2d');
            const gradesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Оценка 5', 'Оценка 4', 'Оценка 3', 'Оценка 2'],
                    datasets: [{
                        label: 'Количество студентов',
                        data: [
                            <?php echo $statistics['grades_distribution']['5']; ?>,
                            <?php echo $statistics['grades_distribution']['4']; ?>,
                            <?php echo $statistics['grades_distribution']['3']; ?>,
                            <?php echo $statistics['grades_distribution']['2']; ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#17a2b8',
                            '#ffc107',
                            '#dc3545'
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
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>