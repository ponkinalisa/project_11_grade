<?php
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) and $_SESSION['status'] != 'student'){
    header('Location: ../../index.php');
    exit;
}

$test_id = $_GET['test_id'] ?? null;
$results_data = [];
$test_info = [];
$statistics = [];

if ($test_id) {
    try {
        $sql = "SELECT t.name as test_name, t.*, u.name, u.surname 
                FROM tests t 
                JOIN users u ON t.author_id = u.id 
                WHERE t.id = :test_id AND t.author_id = :author_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id, 'author_id' => $_SESSION['id']]);
        $test_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$test_info) {
            die("Тест не найден или у вас нет прав для просмотра результатов");
        }
        
        $sql = "SELECT r.*, u.name, u.surname, 
                       (SELECT COUNT(*) FROM test_results WHERE test_id = :test_id) as total_attempts,
                       (SELECT COUNT(DISTINCT student_id) FROM test_results WHERE test_id = :test_id) as unique_students
                FROM test_results r 
                JOIN users u ON r.student_id = u.id 
                WHERE r.test_id = :test_id 
                ORDER BY r.score DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id]);
        $results_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
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
    <link rel="stylesheet" type="text/css" href="../css/teacher_test_results.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
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
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Результаты теста: <?php echo htmlspecialchars($test_info['test_name'] ?? 'Неизвестный тест'); ?></h1>
                <a href="teacher_main.php" class="back-btn">← Назад к тестам</a>
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
                    
                    <div id="results" class="tab-content active">
                        <div class="filters">
                            
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
                                    <th>Баллы</th>
                                    <th>Оценка</th>
                                    <th>Дата прохождения</th>
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
                                    <td><?php echo htmlspecialchars($result['name'] . ' ' . $result['surname']); ?></td>
                                    <td class="score-cell <?php echo $grade_class; ?>">
                                        <?php echo $result['score']; ?>/<?php echo $test_info['count_tasks']; ?>
                                        (<?php echo round($percentage); ?>%)
                                    </td>
                                    <td><span class="<?php echo $grade_class; ?>"><?php echo $grade; ?></span></td>
                                    <td><span><?php echo $result['date']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
                <div class="results-container">
                    <h3>Экспорт результатов</h3>
                    <div class="export-options">
                        <button class="btn btn-outline" onclick="printResults()">Печать результатов</button>
                    </div>
                </div>
            <?php endif; ?>
            <div class="action-buttons">
                <a href="teacher_main.php" class="btn btn-secondary">Назад к тестам</a>
                <?php if ($test_id): ?>
                    <a href="teacher_edit_test.php?test_id=<?php echo $test_id; ?>" class="btn btn-outline">Редактировать тест</a>
                <?php endif; ?>
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
        
        function showDetailedResult(resultId) {
            alert('Детальная информация о результате с ID: ' + resultId);
        }
        
        function printResults() {
            window.print();
        }
        
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