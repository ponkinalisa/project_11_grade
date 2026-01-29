<?php
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) || $_SESSION['status'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}


$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'date_desc'; 

try {
    $sql = "SELECT * FROM tests";
    
    $where_conditions = [];
    $params = [];

    if (!empty($search_query)) {
        $where_conditions[] = "(name LIKE :search OR description LIKE :search)";
        $params['search'] = "%$search_query%";
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Ошибка: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление тестами</title>
    <link rel="stylesheet" type="text/css" href="../css/tests.css">
    <link rel="stylesheet" type="text/css" href="../css/teacher_main.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>  Управление тестами</h1>
            <a href="admin.php" class="back-link">← Назад к админ-панели</a>
        </div>
        
        <div class="controls">
            <div class="search-box">
                <form method="GET" class="search-form">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                    <input type="text" name="search" 
                           placeholder="Поиск по названию или описанию теста..." 
                           class="search-input"
                           value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="search-btn">Найти</button>
                    <?php if ($search_query): ?>
                        <a href="tests.php?sort=<?php echo urlencode($sort_by); ?>" 
                           class="action-btn btn-view" style="padding: 10px 15px;">
                            Сбросить
                        </a>
                    <?php endif; ?>
                </form>
            </div>

        </div>
        
        <div class="tests-list">
            <?php if (empty($tests)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📚</div>
                    <h3>Тесты не найдены</h3>
                    <p><?php echo $search_query ? 'Попробуйте изменить поисковый запрос' : 'В системе пока нет тестов'; ?></p>
                </div>
            <?php else: ?>
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
                            
                            <a href="../php/delete_test.php?test_id='.$test['id'].'" class="test-btn delete-btn">Удалить</a>
                        </div>
                    </div>');
             }?> <?php endif; ?>
        </div>
    </div>
</body>
</html>