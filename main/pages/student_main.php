<?php 
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login'])){
    header('Location: ../../index.php');
    exit;
}

# работа с бд для вывода тестов пользователя
try{
$sql = "SELECT * FROM tests WHERE is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <button class="profile-btn" id="profileBtn">
                        <span>Личный кабинет</span>
                        <span>→</span>
                    </button>
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
                        <div class="stat-value">12</div>
                        <div class="stat-label">Пройдено тестов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">87%</div>
                        <div class="stat-label">Средний результат</div>
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
                    <div class="test-card">
                        <div class="test-header">
                            <span class="test-subject">Физика</span>
                            <h3 class="test-title">Законы Ньютона</h3>
                            <div class="test-info">
                                <span>92%</span>
                                <span>Завершено 2 дня назад</span>
                            </div>
                        </div>
                        <div class="test-body">
                            <p class="test-description">
                                Проверка понимания трех законов Ньютона и их применения к решению задач.
                            </p>
                        </div>
                        <div class="test-footer">
                            <div class="completed-badge">
                                <span>✓</span>
                                <span>Завершено</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-card">
                        <div class="test-header">
                            <span class="test-subject">Литература</span>
                            <h3 class="test-title">Творчество Пушкина</h3>
                            <div class="test-info">
                                <span>78%</span>
                                <span>Завершено 5 дней назад</span>
                            </div>
                        </div>
                        <div class="test-body">
                            <p class="test-description">
                                Основные произведения, герои и темы в творчестве Александра Сергеевича Пушкина.
                            </p>
                        </div>
                        <div class="test-footer">
                            <div class="completed-badge">
                                <span>✓</span>
                                <span>Завершено</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

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
        document.getElementById('profileBtn').addEventListener('click', function() {
            alert('Переход в личный кабинет');
        });
        
        const startButtons = document.querySelectorAll('.start-test-btn');
        startButtons.forEach(button => {
            button.addEventListener('click', function() {
                const testTitle = this.closest('.test-card').querySelector('.test-title').textContent;
                alert(`Начинаем тест: "${testTitle}"`);
            });
        });
    </script>
</body>
</html>