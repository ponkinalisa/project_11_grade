<?php 
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login'])){
    header('Location: ../../index.php');
    exit;
}

$user_id = $_SESSION['id'];

// Получаем данные пользователя
try {
    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data) {
        die("Пользователь не найден");
    }
} catch(PDOException $e) {
    die("Ошибка при получении данных пользователя: " . $e->getMessage());
}

// Получаем статистику по пройденным тестам
$statistics = [
    'total_tests' => 0,
    'average_score' => 0,
    'best_score' => 0,
    'last_test_date' => null,
    'grades_distribution' => [
        '5' => 0,
        '4' => 0,
        '3' => 0,
        '2' => 0
    ],
    'by_subject' => [],
    'by_date' => []
];

// Получаем все результаты тестов студента
try {
    $sql = "SELECT tr.*, t.name as test_name, t.description, t.count_tasks, 
                   t.grade5, t.grade4, t.grade3, t.author_id,
                   u.name as author_first, u.surname as author_last
            FROM test_results tr 
            JOIN tests t ON tr.test_id = t.id 
            JOIN users u ON t.author_id = u.id
            WHERE tr.student_id = :student_id 
            ORDER BY tr.date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['student_id' => $user_id]);
    $test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $statistics['total_tests'] = count($test_results);
    
    if (!empty($test_results)) {
        $total_score = 0;
        $total_percentage = 0;
        $best_percentage = 0;
        
        foreach ($test_results as $result) {
            // Рассчитываем процент выполнения
            $percentage = round(($result['score'] / $result['count_tasks']) * 100, 1);
            $total_percentage += $percentage;
            
            // Определяем оценку
            if ($percentage >= $result['grade5']) {
                $grade = '5';
                $statistics['grades_distribution']['5']++;
            } elseif ($percentage >= $result['grade4']) {
                $grade = '4';
                $statistics['grades_distribution']['4']++;
            } elseif ($percentage >= $result['grade3']) {
                $grade = '3';
                $statistics['grades_distribution']['3']++;
            } else {
                $grade = '2';
                $statistics['grades_distribution']['2']++;
            }
            
            // Лучший результат
            if ($percentage > $best_percentage) {
                $best_percentage = $percentage;
                $statistics['best_score'] = $percentage;
            }
            
            // Группировка по предмету (предположим, что предмет в названии теста)
            $subject = "Общий"; // В реальной системе нужно брать из отдельного поля
            if (!isset($statistics['by_subject'][$subject])) {
                $statistics['by_subject'][$subject] = [
                    'count' => 0,
                    'average' => 0,
                    'total_percentage' => 0
                ];
            }
            $statistics['by_subject'][$subject]['count']++;
            $statistics['by_subject'][$subject]['total_percentage'] += $percentage;
            
            // Группировка по дате (по месяцам)
            $month = date('Y-m', strtotime($result['date']));
            if (!isset($statistics['by_date'][$month])) {
                $statistics['by_date'][$month] = [
                    'count' => 0,
                    'average' => 0,
                    'total_percentage' => 0
                ];
            }
            $statistics['by_date'][$month]['count']++;
            $statistics['by_date'][$month]['total_percentage'] += $percentage;
            
            $total_score += $result['score'];
        }
        
        // Рассчитываем средние значения
        $statistics['average_score'] = round($total_percentage / $statistics['total_tests'], 1);
        
        // Рассчитываем средние по предметам
        foreach ($statistics['by_subject'] as $subject => $data) {
            $statistics['by_subject'][$subject]['average'] = 
                round($data['total_percentage'] / $data['count'], 1);
        }
        
        // Рассчитываем средние по месяцам
        foreach ($statistics['by_date'] as $month => $data) {
            $statistics['by_date'][$month]['average'] = 
                round($data['total_percentage'] / $data['count'], 1);
        }
        
        // Последняя дата теста
        $statistics['last_test_date'] = $test_results[0]['date'];
    }
    
} catch(PDOException $e) {
    die("Ошибка при получении статистики: " . $e->getMessage());
}

// Выход из системы
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ../../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | Образовательная платформа</title>
    <link rel="stylesheet" type="text/css" href="../css/student_main.css">
    <link rel="stylesheet" type="text/css" href="../css/student_account.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="student_main.php" class="profile-btn">
                        <span>На главную</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="profile-container">
                <!-- Информация о пользователе -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo(mb_substr($_SESSION['i'], 0, 1) . mb_substr($_SESSION['f'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-name">
                            <?php echo htmlspecialchars($user_data['name'] . ' ' . $user_data['surname'] . ' ' . ($user_data['patronymic'] ?? '')); ?>
                        </h1>
                        <div class="profile-email">Логин: <?php echo htmlspecialchars($user_data['login']); ?></div>
                    </div>
                </div>
                
                <!-- Общая статистика -->
                <h2>Общая статистика</h2>
                
                <?php if ($statistics['total_tests'] == 0): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📊</div>
                        <h3>Статистика пока отсутствует</h3>
                        <p>Вы еще не прошли ни одного теста.</p>
                        <a href="student_main.php" class="btn btn-primary">Пройти тесты</a>
                    </div>
                <?php else: ?>
                    <div class="stats-overview">
                        <div class="stat-card-large">
                            <div class="stat-value-large"><?php echo $statistics['total_tests']; ?></div>
                            <div class="stat-label-large">Всего тестов пройдено</div>
                        </div>
                        <div class="stat-card-large">
                            <div class="stat-value-large"><?php echo $statistics['average_score']; ?>%</div>
                            <div class="stat-label-large">Средний результат</div>
                        </div>
                        <div class="stat-card-large">
                            <div class="stat-value-large"><?php echo $statistics['best_score']; ?>%</div>
                            <div class="stat-label-large">Лучший результат</div>
                        </div>
                        <div class="stat-card-large">
                            <div class="stat-value-large">
                                <?php echo date('d.m.Y', strtotime($statistics['last_test_date'])); ?>
                            </div>
                            <div class="stat-label-large">Последний тест</div>
                        </div>
                    </div>
                    
                    <!-- Распределение по оценкам -->
                    <div class="grades-distribution">
                        <h3>Распределение по оценкам</h3>
                        <div class="grades-grid">
                            <div class="grade-item grade-5">
                                <div class="grade-count"><?php echo $statistics['grades_distribution']['5']; ?></div>
                                <div>Оценка "5"</div>
                            </div>
                            <div class="grade-item grade-4">
                                <div class="grade-count"><?php echo $statistics['grades_distribution']['4']; ?></div>
                                <div>Оценка "4"</div>
                            </div>
                            <div class="grade-item grade-3">
                                <div class="grade-count"><?php echo $statistics['grades_distribution']['3']; ?></div>
                                <div>Оценка "3"</div>
                            </div>
                            <div class="grade-item grade-2">
                                <div class="grade-count"><?php echo $statistics['grades_distribution']['2']; ?></div>
                                <div>Оценка "2"</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Статистика по месяцам -->
                    <div class="monthly-stats">
                        <h3>Статистика по месяцам</h3>
                        <?php if (!empty($statistics['by_date'])): ?>
                            <div class="month-grid">
                                <?php 
                                // Сортируем по дате (от новых к старым)
                                krsort($statistics['by_date']);
                                $counter = 0;
                                foreach ($statistics['by_date'] as $month => $data): 
                                    if ($counter++ < 6): // Показываем только последние 6 месяцев
                                ?>
                                    <div class="month-card">
                                        <div class="month-name">
                                            <?php 
                                            $month_names = [
                                                '01' => 'Январь', '02' => 'Февраль', '03' => 'Март',
                                                '04' => 'Апрель', '05' => 'Май', '06' => 'Июнь',
                                                '07' => 'Июль', '08' => 'Август', '09' => 'Сентябрь',
                                                '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
                                            ];
                                            $month_num = date('m', strtotime($month . '-01'));
                                            $year = date('Y', strtotime($month . '-01'));
                                            echo $month_names[$month_num] . ' ' . $year;
                                            ?>
                                        </div>
                                        <div class="month-average">
                                            <?php echo $data['average']; ?>%
                                        </div>
                                        <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                            <?php echo $data['count']; ?> тест(ов)
                                        </div>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
                                Нет данных по месяцам
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- История прохождения тестов -->
                    <div class="test-history">
                        <h3>История прохождения тестов</h3>
                        
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Тест</th>
                                    <th>Преподаватель</th>
                                    <th>Баллы</th>
                                    <th>Процент</th>
                                    <th>Оценка</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($test_results as $result): 
                                    $percentage = round(($result['score'] / $result['count_tasks']) * 100, 1);
                                    
                                    // Определяем оценку и класс для цвета
                                    if ($percentage >= $result['grade5']) {
                                        $grade = '5';
                                        $percentage_class = 'percentage-excellent';
                                    } elseif ($percentage >= $result['grade4']) {
                                        $grade = '4';
                                        $percentage_class = 'percentage-good';
                                    } elseif ($percentage >= $result['grade3']) {
                                        $grade = '3';
                                        $percentage_class = 'percentage-satisfactory';
                                    } else {
                                        $grade = '2';
                                        $percentage_class = 'percentage-poor';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($result['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($result['test_name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['author_first'] . ' ' . $result['author_last']); ?></td>
                                    <td><?php echo $result['score']; ?>/<?php echo $result['count_tasks']; ?></td>
                                    <td class="percentage-cell <?php echo $percentage_class; ?>">
                                        <?php echo $percentage; ?>%
                                    </td>
                                    <td><span class="<?php echo $percentage_class; ?>"><?php echo $grade; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <!-- Кнопка выхода -->
                <form method="post" class="logout-form">
                    <button type="submit" name="logout" class="btn btn-danger">
                        <span>Выйти из системы</span>
                        <span>→</span>
                    </button>
                </form>
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
        // Инициализация графиков (если нужно)
        document.addEventListener('DOMContentLoaded', function() {
            // Здесь можно добавить графики с использованием Chart.js
            console.log('Личный кабинет загружен');
        });
    </script>
</body>
</html>