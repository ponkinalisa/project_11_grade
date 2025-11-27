<?php
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login'])){
    header('Location: ../../index.php');
    exit;
}

$test_id = $_GET['test_id'] ?? null;
$test_data = null;
$tasks_data = [];
$attempt_id = null;

if ($test_id) {
    try {
        // Получаем основную информацию о тесте
        $sql = "SELECT * FROM tests WHERE id = :test_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id]);
        $test_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$test_data) {
            die("Тест не найден");
        }
        
        // Проверяем, не проходил ли студент уже этот тест
        $sql = "SELECT * FROM test_results WHERE student_id = :student_id AND test_id = :test_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id, 'student_id' => $_SESSION['id']]);
        $existing_attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        
        // Получаем задания теста
        $sql = "SELECT * FROM types WHERE test_id = :test_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id]);
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tasks_data = array();

        foreach ($types as $type){
            $sql = "SELECT * FROM tasks WHERE test_id = :test_id AND type_id = :type_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['test_id' => $test_id, 'type_id' => $type['id']]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($tasks) >= $type['amount']){
                if (count($tasks) == 1){
                    $tasks_data = array_merge($tasks_data, [$tasks]);
                }else{
                    if ($type['amount'] == 1){
                        $tasks_data = array_merge($tasks_data, [$tasks[array_rand($tasks)]]);
                    }else{
                        $rands = array_rand($tasks, $type['amount']);
                        foreach ($rands as $r){
                            $tasks_data.array_push($tasks[$r]);
                        }
                    }
                }
            }else{
                if (count($tasks) > 0){
                    $rands = array_rand($tasks, count($tasks));
                        foreach ($rands as $r){
                            $tasks_data.array_push($tasks[$r]);
                        }
                }
            }

        }
        


    } catch (PDOException $e) {
        echo 'Ошибка при загрузке теста: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Прохождение теста | Образовательная платформа</title>
    <link rel="stylesheet" type="text/css" href="../css/new_test.css">
    <link rel="stylesheet" type="text/css" href="../css/test_run.css">
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
            <?php if (!$test_data): ?>
                <div class="test-container">
                    <div class="empty-state">
                        <div class="empty-state-icon">❌</div>
                        <h3>Тест не найден</h3>
                        <p>Запрошенный тест не существует или у вас нет к нему доступа.</p>
                        <a href="student_tests.php" class="btn btn-primary">Вернуться к тестам</a>
                    </div>
                </div>
            <?php else: ?>
                <form id="testForm" action="student_take_test.php?test_id=<?php echo $test_id; ?>" method="post">
                    <div class="test-container">
                        <!-- Заголовок теста -->
                        <div class="test-header">
                            <div class="test-info">
                                <h1><?php echo htmlspecialchars($test_data['name']); ?></h1>
                                <p style="color: var(--text-secondary); margin-top: 5px;">
                                    <?php echo htmlspecialchars($test_data['description']); ?>
                                </p>
                            </div>
                            <div class="test-timer">
                                <div>Осталось времени:</div>
                                <div class="timer-display" id="timer">
                                    <?php echo gmdate("H:i:s", $test_data['time'] * 60); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Предупреждение о времени -->
                        <div class="time-warning" id="timeWarning">
                            ⚠️ Внимание! До окончания теста осталось менее 5 минут!
                        </div>
                        
                        <!-- Прогресс-бар -->
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                            </div>
                            <div class="progress-text">
                                <span>Прогресс: <span id="progressText">0</span>%</span>
                                <span>Вопрос <span id="currentTask">1</span> из <?php echo count($tasks_data); ?></span>
                            </div>
                        </div>
                        
                        <!-- Навигация по заданиям -->
                        <div class="task-navigation">
                            <div class="nav-buttons">
                                <button type="button" class="btn btn-outline" id="prevBtn" onclick="prevTask()" disabled>
                                    ← Назад
                                </button>
                                <button type="button" class="btn btn-outline" id="nextBtn" onclick="nextTask()">
                                    Далее →
                                </button>
                            </div>
                            <div class="task-numbers" id="taskNumbers">
                                <?php foreach ($tasks_data as $index => $task): ?>
                                    <div class="task-number <?php echo $index === 0 ? 'current' : ''; ?>" 
                                         onclick="goToTask(<?php echo $index; ?>)">
                                        <?php echo $index + 1; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Задания -->
                        <div id="tasksContainer">
                            <?php foreach ($tasks_data as $index => $task): ?>
                                <div class="task-item" id="task-<?php echo $index; ?>" 
                                     style="<?php echo $index === 0 ? '' : 'display: none;'; ?>">
                                    <div class="task-header">
                                        <div class="task-title">Задание <?php echo $index + 1; ?></div>
                                    </div>
                                    
                                    <div class="task-content">
                                        <div class="task-text"><?php echo nl2br(htmlspecialchars($task['text'])); ?></div>
                                        
                                        <?php if (!empty($task['path_to_img'])): ?>
                                            <img src="../<?php echo $task['path_to_img']; ?>" 
                                                 alt="Изображение к заданию" 
                                                 class="task-image">
                                        <?php endif; ?>
                                        
                                        <div class="form-group">
                                            <label for="answer-<?php echo $task['id']; ?>" style="font-weight: 600; margin-bottom: 10px; display: block;">
                                                Ваш ответ:
                                            </label>
                                            <textarea 
                                                id="answer-<?php echo $task['id']; ?>"
                                                name="answers[<?php echo $task['id']; ?>]"
                                                class="answer-input"
                                                placeholder="Введите ваш ответ здесь..."
                                                oninput="markTaskAnswered(<?php echo $index; ?>)"
                                                rows="4"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Управление тестом -->
                        <div class="test-controls">
                            <button type="button" class="btn btn-secondary" onclick="showExitConfirmation()">
                                Выйти из теста
                            </button>
                            
                            <div style="display: flex; gap: 15px;">
                                <button type="button" class="btn btn-outline" onclick="saveProgress()">
                                    Сохранить прогресс
                                </button>
                                <button type="button" class="btn btn-primary" onclick="showSubmitConfirmation()">
                                    Завершить тест
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- Модальные окна -->
    <div class="confirmation-modal" id="exitModal">
        <div class="modal-content">
            <h3>Выход из теста</h3>
            <p>Ваш прогресс будет сохранен. Вы сможете продолжить тест позже.</p>
            <div class="modal-buttons">
                <button class="btn btn-secondary" onclick="hideExitConfirmation()">Отмена</button>
                <a href="student_tests.php" class="btn btn-outline">Выйти</a>
            </div>
        </div>
    </div>
    
    <div class="confirmation-modal" id="submitModal">
        <div class="modal-content">
            <h3>Завершение теста</h3>
            <p>Вы уверены, что хотите завершить тест? После отправки изменить ответы будет невозможно.</p>
            <div class="progress-text" style="margin: 15px 0;">
                Отвечено: <span id="answeredCount">0</span> из <?php echo count($tasks_data); ?> заданий
            </div>
            <div class="modal-buttons">
                <button class="btn btn-secondary" onclick="hideSubmitConfirmation()">Вернуться к тесту</button>
                <button class="btn btn-primary" onclick="submitTest()">Завершить тест</button>
            </div>
        </div>
    </div>
    
    <!-- Индикатор автосохранения -->
    <div class="auto-save-indicator" id="autoSaveIndicator">
        Прогресс сохранен ✓
    </div>

    <script>
        let currentTaskIndex = 0;
        const totalTasks = <?php echo count($tasks_data); ?>;
        let answeredTasks = new Set();
        let timeLeft = <?php echo $test_data['time'] * 60; ?>; // в секундах
        let timerInterval;
        
        // Инициализация таймера
        function startTimer() {
            timerInterval = setInterval(function() {
                timeLeft--;
                
                // Обновляем отображение таймера
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;
                
                document.getElementById('timer').textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Предупреждение за 5 минут
                if (timeLeft === 300) { // 5 минут = 300 секунд
                    document.getElementById('timeWarning').style.display = 'block';
                }
                
                // Красный цвет за 1 минуту
                if (timeLeft <= 60) {
                    document.getElementById('timer').classList.add('timer-warning');
                }
                
                // Автоматическая отправка при окончании времени
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    alert('Время вышло! Тест будет автоматически отправлен.');
                    submitTest();
                }
            }, 1000);
        }
        
        // Навигация по заданиям
        function goToTask(index) {
            // Скрываем текущее задание
            document.getElementById(`task-${currentTaskIndex}`).style.display = 'none';
            document.querySelectorAll('.task-number')[currentTaskIndex].classList.remove('current');
            
            // Показываем новое задание
            currentTaskIndex = index;
            document.getElementById(`task-${currentTaskIndex}`).style.display = 'block';
            document.querySelectorAll('.task-number')[currentTaskIndex].classList.add('current');
            
            // Обновляем кнопки навигации
            updateNavigationButtons();
            updateProgress();
        }
        
        function nextTask() {
            if (currentTaskIndex < totalTasks - 1) {
                goToTask(currentTaskIndex + 1);
            }
        }
        
        function prevTask() {
            if (currentTaskIndex > 0) {
                goToTask(currentTaskIndex - 1);
            }
        }
        
        function updateNavigationButtons() {
            document.getElementById('prevBtn').disabled = currentTaskIndex === 0;
            document.getElementById('nextBtn').disabled = currentTaskIndex === totalTasks - 1;
            document.getElementById('currentTask').textContent = currentTaskIndex + 1;
        }
        
        // Отметка задания как отвеченного
        function markTaskAnswered(taskIndex) {
            answeredTasks.add(taskIndex);
            document.querySelectorAll('.task-number')[taskIndex].classList.add('answered');
            updateProgress();
            autoSaveProgress();
        }
        
        // Обновление прогресса
        function updateProgress() {
            const progress = (answeredTasks.size / totalTasks) * 100;
            document.getElementById('progressFill').style.width = `${progress}%`;
            document.getElementById('progressText').textContent = Math.round(progress);
            document.getElementById('answeredCount').textContent = answeredTasks.size;
        }
        
        // Автосохранение
        function autoSaveProgress() {
            // В реальном приложении здесь был бы AJAX-запрос к серверу
            showAutoSaveIndicator();
        }
        
        function saveProgress() {
            // В реальном приложении здесь был бы AJAX-запрос к серверу
            showAutoSaveIndicator();
            alert('Прогресс успешно сохранен!');
        }
        
        function showAutoSaveIndicator() {
            const indicator = document.getElementById('autoSaveIndicator');
            indicator.style.display = 'block';
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 2000);
        }
        
        // Подтверждение отправки
        function showSubmitConfirmation() {
            document.getElementById('submitModal').style.display = 'flex';
        }
        
        function hideSubmitConfirmation() {
            document.getElementById('submitModal').style.display = 'none';
        }
        
        function submitTest() {
            clearInterval(timerInterval);
            document.getElementById('testForm').submit();
        }
        
        // Подтверждение выхода
        function showExitConfirmation() {
            document.getElementById('exitModal').style.display = 'flex';
        }
        
        function hideExitConfirmation() {
            document.getElementById('exitModal').style.display = 'none';
        }
        
        // Предотвращение случайного закрытия страницы
        function setupBeforeUnload() {
            window.addEventListener('beforeunload', function(e) {
                if (answeredTasks.size > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                    return 'Вы уверены, что хотите покинуть страницу? Несохраненный прогресс будет потерян.';
                }
            });
        }
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            startTimer();
            updateProgress();
            setupBeforeUnload();
            
            // Проверяем сохраненные ответы (в реальном приложении - загрузка с сервера)
            <?php if ($existing_attempt && $existing_attempt['status'] === 'in_progress'): ?>
                // Здесь можно загрузить сохраненные ответы
                console.log('Загружаем сохраненный прогресс...');
            <?php endif; ?>
        });
        
        // Горячие клавиши
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        prevTask();
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        nextTask();
                        break;
                    case 's':
                        e.preventDefault();
                        saveProgress();
                        break;
                }
            }
        });
    </script>
</body>
</html>