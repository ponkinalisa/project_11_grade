<?php 
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) and $_SESSION['status'] != 'teacher'){
    header('Location: ../../index.php');
    exit;
}

# работа с бд для вывода тестов пользователя
try{
$sql = "SELECT * FROM tests WHERE is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$tests_all = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT * FROM test_results WHERE student_id = :id AND mark IS NULL";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $_SESSION['id']]);
$cur_tests = $stmt->fetch(PDO::FETCH_ASSOC);
if ($cur_tests){
    header('Location: test_run.php?test_id='.$cur_tests['test_id']);
    exit;
}
$tests = array();

foreach ($tests_all as $test){
    $sql = "SELECT * FROM test_results WHERE student_id = :id AND test_id = :test_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $_SESSION['id'], 'test_id' => $test['id']]);
    $in_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($in_results) == 0){
        array_push($tests, $test);
    }
}
$sql = "SELECT * FROM test_results WHERE student_id = :id AND mark > 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $_SESSION['id']]);
$tests_in_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sred_z = 0;
foreach ($tests_in_result as $res){
    $sred_z += $res['mark'];
}
if (count($tests_in_result) > 0){
    $sred_z = round($sred_z / count($tests_in_result), 2);
}
}catch(PDOException $e){
    echo $e->getMessage();
}

?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница | Образовательная платформа</title>
    <link rel="stylesheet" type="text/css" href="../css/student_main.css">
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
                    <a class="profile-btn" href="student_account.php">
                        <span>Личный кабинет</span>
                        <span>→</span>
                    </a>
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
            <section class="welcome-section">
                <div class="welcome-card">
                    <h1>Добро пожаловать, <?php echo($_SESSION['i']); ?>!</h1>
                    <p class="welcome-text">Продолжайте обучение и улучшайте свои результаты</p>
                </div>
            </section>
            
            <section class="stats-section">
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo(count($tests_in_result))?></div>
                        <div class="stat-label">Пройдено тестов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo($sred_z) ?> </div>
                        <div class="stat-label">Средняя оценка</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($tests)?></div>
                        <div class="stat-label">Доступных теста</div>
                    </div>
                </div>
            </section>
            
            <section class="tests-section">
                <h2 class="section-title">
                    <span class="section-title-icon">📚</span>
                    Доступные тесты
                </h2>
                
                <div class="tests-grid">
                <?php foreach ($tests as $test){
                        echo('<div class="test-card"><div class="test-header">');
                        echo('<span class="test-subject">Математика</span>');
                        echo('<h3 class="test-title">'.$test['name'].'</h3>');
                        echo('<div class="test-info"><span>'.$test['count'].' вопросов</span><span>'.$test['time'].' минут</span></div>');
                        echo('</div><div class="test-body"><p class="test-description">'.$test['description'].'</p>');
                        echo('</div>
                        <div class="test-footer">
                        <a href="test_run.php?test_id='.$test['id'].'" class="start-test-btn">Начать тест</a>
                        </div></div>');   
                    }?>
                </div>
            </section>
            
            <section class="completed-tests-section">
                <h2 class="section-title">
                    <span class="section-title-icon">✅</span>
                    Недавно пройденные тесты
                </h2>
                
                <div class="tests-grid">
                    <?php 
                    $arr = array();
                    foreach ($tests_in_result as $test_res){
                        try{
                            if (!in_array($test_res['test_id'], $arr)){
                                $sql = "SELECT * FROM tests WHERE id = :id";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute(['id' => $test_res['test_id']]);
                            $test = $stmt->fetch(PDO::FETCH_ASSOC);

                            $percent = round($test_res['score'] / $test['count_tasks'] * 100);

                        echo('<div class="test-card"><div class="test-header">');
                        echo('<span class="test-subject">Математика</span>');
                        echo('<h3 class="test-title">'.$test['name'].'</h3>');
                        echo('<div class="test-info"><span>'.$percent.'%</span><br><span>Оценка: '.$test_res['mark'].'</span><br><span>Завершено: '.$test_res['date'].'</span></div>');
                        echo('</div><div class="test-body"><p class="test-description">'.$test['description'].'</p>');
                        echo('</div>
                        <div class="test-footer">
                            <div class="completed-badge">
                                <span>✓</span>
                                <span>Завершено</span>
                            </div>
                        </div></div>');

                                array_push($arr, $test_res['test_id']);
                            }


                        }catch (Exception $e){
                            echo($e->getMessage());
                        }
                    }
                    ?>
                
                </div>
            </section>
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
        localStorage.clear();
    </script>
</body>
</html>