<?php 
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) and $_SESSION['status'] != 'student'){
    header('Location: ../../index.php');
    exit;
}

$teacher_id = $_SESSION['id'];

$test_id = $_GET['test_id'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$sort_by = $_GET['sort'] ?? 'date_desc';

$sql = "SELECT id, name, is_active FROM tests WHERE author_id = :id ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $teacher_id]);
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$overall_stats = [];
$detailed_stats = [];
$graph_data = [];

if (!empty($tests)) {
    try {
        $sql = "SELECT tr.*, t.name as test_name, t.count_tasks, t.grade5, t.grade4, t.grade3,
                       u.surname, u.name, u.patronymic,
                       ROUND((tr.score * 100.0 / t.count_tasks), 1) as percentage,
                       CASE 
                           WHEN (tr.score * 100.0 / t.count_tasks) >= t.grade5 THEN '5'
                           WHEN (tr.score * 100.0 / t.count_tasks) >= t.grade4 THEN '4'
                           WHEN (tr.score * 100.0 / t.count_tasks) >= t.grade3 THEN '3'
                           ELSE '2'
                       END as grade
                FROM test_results tr 
                JOIN tests t ON tr.test_id = t.id 
                JOIN users u ON tr.student_id = u.id
                WHERE t.author_id = :teacher_id";
        
        $params = ['teacher_id' => $teacher_id];
        
        if ($test_id) {
            $sql .= " AND t.id = :test_id";
            $params['test_id'] = $test_id;
        }
        
        if ($start_date) {
            $sql .= " AND DATE(tr.date) >= :start_date";
            $params['start_date'] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND DATE(tr.date) <= :end_date";
            $params['end_date'] = $end_date;
        }
        
        switch ($sort_by) {
            case 'date_desc':
                $sql .= " ORDER BY tr.date DESC";
                break;
            case 'date_asc':
                $sql .= " ORDER BY tr.date ASC";
                break;
            case 'student_asc':
                $sql .= " ORDER BY u.surname ASC, u.name ASC";
                break;
            case 'student_desc':
                $sql .= " ORDER BY u.surname DESC, u.name DESC";
                break;
            case 'test_asc':
                $sql .= " ORDER BY t.name ASC";
                break;
            case 'test_desc':
                $sql .= " ORDER BY t.name DESC";
                break;
            case 'score_desc':
                $sql .= " ORDER BY percentage DESC";
                break;
            case 'score_asc':
                $sql .= " ORDER BY percentage ASC";
                break;
            default:
                $sql .= " ORDER BY tr.date DESC";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_tests = count($all_results);
        $total_students = 0;
        $total_percentage = 0;
        $grades_distribution = ['5' => 0, '4' => 0, '3' => 0, '2' => 0];
        $test_stats = [];
        $date_stats = [];
        $unique_students = [];
        
        foreach ($all_results as $result) {
            $percentage = floatval($result['percentage']);
            $total_percentage += $percentage;
            
            $grades_distribution[$result['grade']]++;
            
            if (!in_array($result['student_id'], $unique_students)) {
                $unique_students[] = $result['student_id'];
            }
            
            $test_id_key = $result['test_id'];
            if (!isset($test_stats[$test_id_key])) {
                $test_stats[$test_id_key] = [
                    'name' => $result['test_name'],
                    'count' => 0,
                    'total_percentage' => 0,
                    'grades' => ['5' => 0, '4' => 0, '3' => 0, '2' => 0],
                    'students' => []
                ];
            }
            $test_stats[$test_id_key]['count']++;
            $test_stats[$test_id_key]['total_percentage'] += $percentage;
            $test_stats[$test_id_key]['grades'][$result['grade']]++;
            
            if (!in_array($result['student_id'], $test_stats[$test_id_key]['students'])) {
                $test_stats[$test_id_key]['students'][] = $result['student_id'];
            }
            
            $date = date('Y-m-d', strtotime($result['date']));
            if (!isset($date_stats[$date])) {
                $date_stats[$date] = [
                    'count' => 0,
                    'total_percentage' => 0
                ];
            }
            $date_stats[$date]['count']++;
            $date_stats[$date]['total_percentage'] += $percentage;
            
            $detailed_stats[] = $result;
        }
        
        $total_students = count($unique_students);
        $average_percentage = $total_tests > 0 ? round($total_percentage / $total_tests, 1) : 0;
        

        foreach ($test_stats as &$stats) {
            $stats['average_percentage'] = $stats['count'] > 0 ? round($stats['total_percentage'] / $stats['count'], 1) : 0;
            $stats['unique_students'] = count($stats['students']);
        }
        

        ksort($date_stats);
        
        $graph_labels = [];
        $graph_averages = [];
        $graph_counts = [];
        
        $dates = array_keys($date_stats);
        $start_index = max(0, count($dates) - 14);
        
        for ($i = $start_index; $i < count($dates); $i++) {
            $date = $dates[$i];
            $data = $date_stats[$date];
            $average = $data['count'] > 0 ? round($data['total_percentage'] / $data['count'], 1) : 0;
            
            $graph_labels[] = date('d.m', strtotime($date));
            $graph_averages[] = $average;
            $graph_counts[] = $data['count'];
        }
        
        $overall_stats = [
            'total_tests' => $total_tests,
            'total_students' => $total_students,
            'average_percentage' => $average_percentage,
            'grades_distribution' => $grades_distribution,
            'test_stats' => $test_stats,
            'graph_data' => [
                'labels' => $graph_labels,
                'averages' => $graph_averages,
                'counts' => $graph_counts
            ]
        ];
        
    } catch (PDOException $e) {
        echo "Ошибка при получении статистики: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика тестов | Образовательная платформа</title>
    <link rel="stylesheet" type="text/css" href="../css/teacher_main.css">
    <link rel="stylesheet" type="text/css" href="../css/statistics.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">42</div>
                </div>
                
                <nav class="nav-links">
                    <a href="teacher_main.php" class="nav-link">Панель управления</a>
                    <a href="teacher_statistics.php" class="nav-link active">Статистика</a>
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
                <h1>Статистика тестов</h1>
                <a class="add-test-btn" href="teacher_main.php" style="text-decoration: none">
                    <span>←</span>
                    <span>Назад к тестам</span>
                </a>
            </div>
            
            <div class="stats-container">
                <div class="filters-section">
                    <h3>Фильтры статистики</h3>
                    <form method="GET" action="teacher_statistics.php">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label class="filter-label">Тест</label>
                                <select name="test_id" class="filter-select">
                                    <option value="">Все тесты</option>
                                    <?php foreach ($tests as $test): ?>
                                        <option value="<?php echo $test['id']; ?>" 
                                            <?php echo $test_id == $test['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($test['name']); ?>
                                            <?php echo $test['is_active'] ? ' (активен)' : ' (неактивен)'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Начальная дата</label>
                                <input type="date" name="start_date" class="filter-input" 
                                       value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label class="filter-label">Конечная дата</label>
                                <input type="date" name="end_date" class="filter-input" 
                                       value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
                            </div>
                        </div>
                        
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-filtr">
                                Применить фильтры
                            </button>
                            <a href="teacher_statistics.php" class="btn btn-outline">
                                Сбросить фильтры
                            </a>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($detailed_stats)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📊</div>
                        <h3>Нет данных для отображения</h3>
                        <p>По выбранным фильтрам не найдено результатов тестирования.</p>
                        <a href="teacher_statistics.php" class="btn btn-primary">Показать все результаты</a>
                    </div>
                <?php else: ?>
                    <div class="tabs">
                        <div class="tab active" onclick="switchTab('overview')">Обзор</div>
                        <div class="tab" onclick="switchTab('tests')">Статистика по тестам</div>
                        <div class="tab" onclick="switchTab('details')">Детальные результаты</div>
                    </div>
                    

                    <div id="overview" class="tab-content active">
                        <div class="overview-cards">
                            <div class="overview-card">
                                <div class="card-value"><?php echo $overall_stats['total_tests']; ?></div>
                                <div class="card-label">Всего прохождений</div>
                            </div>
                            <div class="overview-card">
                                <div class="card-value"><?php echo $overall_stats['total_students']; ?></div>
                                <div class="card-label">Студентов</div>
                            </div>
                            <div class="overview-card">
                                <div class="card-value"><?php echo $overall_stats['average_percentage']; ?>%</div>
                                <div class="card-label">Средний результат</div>
                            </div>
                            <div class="overview-card">
                                <div class="card-value">
                                    <?php 
                                    $active_tests = array_filter($tests, function($test) {
                                        return $test['is_active'] == 1;
                                    });
                                    echo count($active_tests);
                                    ?>
                                </div>
                                <div class="card-label">Активных тестов</div>
                            </div>
                        </div>
                        <div class="grades-overview">
                            <h3>Распределение по оценкам</h3>
                            <div class="grades-grid">
                                <div class="grade-card grade-5">
                                    <div class="grade-count"><?php echo $overall_stats['grades_distribution']['5']; ?></div>
                                    <div>Оценка "5"</div>
                                    <small>
                                        <?php echo $overall_stats['total_tests'] > 0 ? 
                                            round(($overall_stats['grades_distribution']['5'] / $overall_stats['total_tests']) * 100, 1) : 0; ?>%
                                    </small>
                                </div>
                                <div class="grade-card grade-4">
                                    <div class="grade-count"><?php echo $overall_stats['grades_distribution']['4']; ?></div>
                                    <div>Оценка "4"</div>
                                    <small>
                                        <?php echo $overall_stats['total_tests'] > 0 ? 
                                            round(($overall_stats['grades_distribution']['4'] / $overall_stats['total_tests']) * 100, 1) : 0; ?>%
                                    </small>
                                </div>
                                <div class="grade-card grade-3">
                                    <div class="grade-count"><?php echo $overall_stats['grades_distribution']['3']; ?></div>
                                    <div>Оценка "3"</div>
                                    <small>
                                        <?php echo $overall_stats['total_tests'] > 0 ? 
                                            round(($overall_stats['grades_distribution']['3'] / $overall_stats['total_tests']) * 100, 1) : 0; ?>%
                                    </small>
                                </div>
                                <div class="grade-card grade-2">
                                    <div class="grade-count"><?php echo $overall_stats['grades_distribution']['2']; ?></div>
                                    <div>Оценка "2"</div>
                                    <small>
                                        <?php echo $overall_stats['total_tests'] > 0 ? 
                                            round(($overall_stats['grades_distribution']['2'] / $overall_stats['total_tests']) * 100, 1) : 0; ?>%
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="charts-section">
                            <h3>Динамика успеваемости</h3>
                            <div class="chart-container">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Статистика по тестам -->
                    <div id="tests" class="tab-content">
                        <h3>Статистика по тестам</h3>
                        <div class="test-stats-grid">
                            <?php foreach ($overall_stats['test_stats'] as $test_stat): ?>
                            <div class="test-stat-card">
                                <div class="test-stat-header">
                                    <div class="test-stat-name"><?php echo htmlspecialchars($test_stat['name']); ?></div>
                                    <div class="test-stat-average"><?php echo $test_stat['average_percentage']; ?>%</div>
                                </div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 10px;">
                                    <?php echo $test_stat['count']; ?> прохождений, 
                                    <?php echo $test_stat['unique_students']; ?> студентов
                                </div>
                                <div class="test-stat-grades">
                                    <div class="mini-grade mini-grade-5" title="Оценок '5': <?php echo $test_stat['grades']['5']; ?>">
                                        5: <?php echo $test_stat['grades']['5']; ?>
                                    </div>
                                    <div class="mini-grade mini-grade-4" title="Оценок '4': <?php echo $test_stat['grades']['4']; ?>">
                                        4: <?php echo $test_stat['grades']['4']; ?>
                                    </div>
                                    <div class="mini-grade mini-grade-3" title="Оценок '3': <?php echo $test_stat['grades']['3']; ?>">
                                        3: <?php echo $test_stat['grades']['3']; ?>
                                    </div>
                                    <div class="mini-grade mini-grade-2" title="Оценок '2': <?php echo $test_stat['grades']['2']; ?>">
                                        2: <?php echo $test_stat['grades']['2']; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Детальные результаты -->
                    <div id="details" class="tab-content">
                        <div class="results-section">
                            <div class="table-controls">
                                <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                    Показано <?php echo count($detailed_stats); ?> записей
                                    <?php if ($test_id): ?> по выбранному тесту<?php endif; ?>
                                </div>
                            </div>
                            
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th>Студент</th>
                                        <th>Тест</th>
                                        <th>Дата</th>
                                        <th>Баллы</th>
                                        <th>Результат</th>
                                        <th>Оценка</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detailed_stats as $result): 
                                        $percentage = floatval($result['percentage']);
                                        
                                        $full_name = $result['surname'] . ' ' . $result['name'];
                                        if (!empty($result['patronymic'])) {
                                            $full_name .= ' ' . $result['patronymic'];
                                        }
                                        
                                        if ($percentage >= $result['grade5']) {
                                            $percentage_class = 'percentage-excellent';
                                        } elseif ($percentage >= $result['grade4']) {
                                            $percentage_class = 'percentage-good';
                                        } elseif ($percentage >= $result['grade3']) {
                                            $percentage_class = 'percentage-satisfactory';
                                        } else {
                                            $percentage_class = 'percentage-poor';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($full_name); ?></td>
                                        <td><?php echo htmlspecialchars($result['test_name']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($result['date'])); ?></td>
                                        <td><?php echo $result['score']; ?>/<?php echo $result['count_tasks']; ?></td>
                                        <td class="percentage-cell <?php echo $percentage_class; ?>">
                                            <?php echo $percentage; ?>%
                                        </td>
                                        <td><span class="<?php echo $percentage_class; ?>"><?php echo $result['grade']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="export-section">
                        <button class="btn btn-outline" onclick="printStatistics()">
                            🖨️ Печать отчета
                        </button>
                    </div>
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
        
        function printStatistics() {
            window.print();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($overall_stats['graph_data']['labels'])): ?>
            const ctx = document.getElementById('performanceChart').getContext('2d');
            const performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($overall_stats['graph_data']['labels']); ?>,
                    datasets: [{
                        label: 'Средний результат (%)',
                        data: <?php echo json_encode($overall_stats['graph_data']['averages']); ?>,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Количество прохождений',
                        data: <?php echo json_encode($overall_stats['graph_data']['counts']); ?>,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Средний результат (%)'
                            },
                            min: 0,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Количество прохождений'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            min: 0
                        }
                    }
                }
            });
            <?php endif; ?>
        });

    </script>
</body>
</html>