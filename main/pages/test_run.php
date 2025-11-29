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
$existing_attempt = null;

if ($test_id) {
    try {
        $sql = "SELECT * FROM tests WHERE id = :test_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id]);
        $test_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$test_data) {
            die("Тест не найден");
        }
        $sql = "SELECT * FROM test_results WHERE student_id = :student_id AND test_id = :test_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id, 'student_id' => $_SESSION['id']]);
        $existing_attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_attempt) {
            // Показываем сообщение, что тест уже пройден
        }
        
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
                if ($type['amount'] == 1){
                    $tasks_data[] = $tasks[array_rand($tasks)];
                } else {
                    $rands = array_rand($tasks, $type['amount']);
                    if ($type['amount'] == 1) {
                        $tasks_data[] = $tasks[$rands];
                    } else {
                        foreach ($rands as $r){
                            $tasks_data[] = $tasks[$r];
                        }
                    }
                }
            } else {
                if (count($tasks) > 0){
                    $rands = array_rand($tasks, count($tasks));
                    if (count($tasks) == 1) {
                        $tasks_data[] = $tasks[$rands];
                    } else {
                        foreach ($rands as $r){
                            $tasks_data[] = $tasks[$r];
                        }
                    }
                }
            }
        }
        shuffle($tasks_data);

    } catch (PDOException $e) {
        echo 'Ошибка при загрузке теста: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $test_id) {
    try {
        $user_answers = $_POST['answers'] ?? [];
        $score = 0;
        $total_tasks = count($tasks_data);
        foreach ($user_answers as $task_id => $user_answer) {
            $user_answer = trim($user_answer);
            $sql = "SELECT * FROM tasks WHERE id = :test_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['test_id' => $task_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            $correct_answer = $task['answer'];
            
            if (!empty($user_answer) && strtolower($user_answer) === strtolower($correct_answer)) {
                $score++;
            }
        }
        
        $percentage = $total_tasks > 0 ? ($score / $total_tasks) * 100 : 0;
        
        $mark = 2;
        if ($percentage >= $test_data['grade5']) {
            $mark = 5;
        } elseif ($percentage >= $test_data['grade4']) {
            $mark = 4;
        } elseif ($percentage >= $test_data['grade3']) {
            $mark = 3;
        }
        
        $sql = "INSERT INTO test_results (student_id, test_id, score, mark, date) 
                VALUES (:student_id, :test_id, :score, :mark, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'student_id' => $_SESSION['id'],
            'test_id' => $test_id,
            'score' => $score,
            'mark' => $mark
        ]);
        
        $result_id = $pdo->lastInsertId();
        
        header("Location: student_test_result.php?attempt_id=" . $result_id);
        exit;
        
    } catch (PDOException $e) {
        echo 'Ошибка при сохранении результатов: ' . $e->getMessage();
        error_log("Ошибка сохранения теста: " . $e->getMessage());
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
            <?php if (!$test_data): ?>
                <div class="test-container">
                    <div class="empty-state">
                        <div class="empty-state-icon">❌</div>
                        <h3>Тест не найден</h3>
                        <p>Запрошенный тест не существует или у вас нет к нему доступа.</p>
                        <a href="student_tests.php" class="btn btn-primary">Вернуться к тестам</a>
                    </div>
                </div>
            <?php elseif ($existing_attempt): ?>
                <div class="test-container">
                    <div class="already-completed">
                        <div class="already-completed-icon">📝</div>
                        <h3>Тест уже пройден</h3>
                        <p>Вы уже проходили этот тест <?php echo date('d.m.Y в H:i', strtotime($existing_attempt['date'])); ?>.</p>
                        <p>Ваш результат: <strong><?php echo $existing_attempt['score']; ?>/<?php echo $test_data['count_tasks']; ?></strong> (оценка: <?php echo $existing_attempt['mark']; ?>)</p>
                        <div style="margin-top: 20px;">
                            <a href="student_tests.php" class="btn btn-primary">Вернуться к тестам</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="test-instructions">
                    <h3>Инструкция по прохождению теста</h3>
                    <ul>
                        <li>На выполнение теста отводится <strong><?php echo $test_data['time']; ?> минут</strong></li>
                        <li>Тест содержит <strong><?php echo count($tasks_data); ?> заданий</strong></li>
                        <li>Для получения оценки "5" необходимо набрать не менее <strong><?php echo $test_data['grade5']; ?>%</strong> правильных ответов</li>
                        <li>Для оценки "4" - не менее <strong><?php echo $test_data['grade4']; ?>%</strong></li>
                        <li>Для оценки "3" - не менее <strong><?php echo $test_data['grade3']; ?>%</strong></li>
                        <li>Менее <strong><?php echo $test_data['grade3']; ?>%</strong> - оценка "2"</li>
                    </ul>
                </div>

                <form id="testForm" action="test_run.php?test_id=<?php echo $test_id; ?>" method="post">
                    <div class="test-container">
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
                        
                        <div class="time-warning" id="timeWarning">
                            ⚠️ Внимание! До окончания теста осталось менее 5 минут!
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                            </div>
                            <div class="progress-text">
                                <span>Прогресс: <span id="progressText">0</span>%</span>
                                <span>Вопрос <span id="currentTask">1</span> из <?php echo count($tasks_data); ?></span>
                            </div>
                        </div>
                        
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
                                            <img src="<?php echo $task['path_to_img']; ?>" 
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
                        
                        <div class="test-controls">
                            <button type="button" class="btn btn-secondary" onclick="showExitConfirmation()">
                                Выйти из теста
                            </button>
                            
                            <button type="button" class="btn" onclick="showSubmitConfirmation()">
                                Завершить тест
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

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
                <button class="btn" onclick="submitTest()">Завершить тест</button>
            </div>
        </div>
    </div>
    
    <div class="auto-save-indicator" id="autoSaveIndicator">
        Прогресс сохранен ✓
    </div>

    <script>
        let currentTaskIndex = 0;
        const totalTasks = <?php echo count($tasks_data); ?>;
        let answeredTasks = new Set();
        let timeLeft = <?php echo $test_data['time'] * 60; ?>; 
        let timerInterval;
        let testStarted = false;
        
        function startTimer() {
            if (!testStarted) {
                testStarted = true;
                timerInterval = setInterval(function() {
                    timeLeft--;
                    const hours = Math.floor(timeLeft / 3600);
                    const minutes = Math.floor((timeLeft % 3600) / 60);
                    const seconds = timeLeft % 60;
                    
                    document.getElementById('timer').textContent = 
                        `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    if (timeLeft === 300) { 
                        document.getElementById('timeWarning').style.display = 'block';
                    }
                    
                    if (timeLeft <= 60) {
                        document.getElementById('timer').classList.add('timer-warning');
                    }
                    
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        alert('Время вышло! Тест будет автоматически отправлен.');
                        submitTest();
                    }
                }, 1000);
            }
        }
        
        function goToTask(index) {
            document.getElementById(`task-${currentTaskIndex}`).style.display = 'none';
            document.querySelectorAll('.task-number')[currentTaskIndex].classList.remove('current');
            
            currentTaskIndex = index;
            document.getElementById(`task-${currentTaskIndex}`).style.display = 'block';
            document.querySelectorAll('.task-number')[currentTaskIndex].classList.add('current');
            document.querySelectorAll('.task-number')[currentTaskIndex].classList.add('visited');
            updateNavigationButtons();
            updateProgress();
            
            if (!testStarted) {
                startTimer();
            }
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
        
        function markTaskAnswered(taskIndex) {
            answeredTasks.add(taskIndex);
            document.querySelectorAll('.task-number')[taskIndex].classList.add('answered');
            updateProgress();
            autoSaveProgress();
        }
        
        function updateProgress() {
            const progress = (answeredTasks.size / totalTasks) * 100;
            document.getElementById('progressFill').style.width = `${progress}%`;
            document.getElementById('progressText').textContent = Math.round(progress);
            document.getElementById('answeredCount').textContent = answeredTasks.size;
        }
        
        function autoSaveProgress() {
            const formData = new FormData(document.getElementById('testForm'));
            const answers = {};
            
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('answers')) {
                    answers[key] = value;
                }
            }
            
            localStorage.setItem(`test_<?php echo $test_id; ?>_answers`, JSON.stringify(answers));
            localStorage.setItem(`test_<?php echo $test_id; ?>_time`, timeLeft.toString());
            
            showAutoSaveIndicator();
        }
        
        function saveProgress() {
            autoSaveProgress();
            alert('Прогресс успешно сохранен!');
        }
        
        function showAutoSaveIndicator() {
            const indicator = document.getElementById('autoSaveIndicator');
            indicator.style.display = 'block';
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 2000);
        }
        
        function loadSavedProgress() {
            const savedAnswers = localStorage.getItem(`test_<?php echo $test_id; ?>_answers`);
            const savedTime = localStorage.getItem(`test_<?php echo $test_id; ?>_time`);
            
            if (savedAnswers) {
                const answers = JSON.parse(savedAnswers);
                
                for (const [key, value] of Object.entries(answers)) {
                    const textarea = document.querySelector(`textarea[name="${key}"]`);
                    
                    if (textarea && value) {
                        textarea.value = value;
                        const taskIndex = Array.from(document.querySelectorAll('.task-item')).findIndex(
                            task => task.querySelector(`textarea[name="${key}"]`)
                        );
                        if (taskIndex !== -1) {
                            answeredTasks.add(taskIndex);
                            document.querySelectorAll('.task-number')[taskIndex].classList.add('answered');
                        }
                    }
                }
                
                updateProgress();
            }
            
            if (savedTime) {
                timeLeft = parseInt(savedTime);
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;
                
                document.getElementById('timer').textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }
        
        function showSubmitConfirmation() {
            document.getElementById('submitModal').style.display = 'flex';
        }
        
        function hideSubmitConfirmation() {
            document.getElementById('submitModal').style.display = 'none';
        }
        
        function submitTest() {
            clearInterval(timerInterval);
            localStorage.removeItem(`test_<?php echo $test_id; ?>_answers`);
            localStorage.removeItem(`test_<?php echo $test_id; ?>_time`);
            document.getElementById('testForm').submit();
        }
        
        function showExitConfirmation() {
            document.getElementById('exitModal').style.display = 'flex';
        }
        
        function hideExitConfirmation() {
            document.getElementById('exitModal').style.display = 'none';
        }
        function setupBeforeUnload() {
            window.addEventListener('beforeunload', function(e) {
                if (answeredTasks.size > 0 && timeLeft > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                    return 'Вы уверены, что хотите покинуть страницу? Ваш прогресс будет сохранен, и вы сможете продолжить позже.';
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            updateProgress();
            setupBeforeUnload();
            loadSavedProgress();
            document.addEventListener('click', function() {
                if (!testStarted) {
                    startTimer();
                }
            }, { once: true });
            
            document.addEventListener('keydown', function() {
                if (!testStarted) {
                    startTimer();
                }
            }, { once: true });
        });
        
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
            if (!e.ctrlKey && !e.metaKey) {
                switch(e.key) {
                    case 'ArrowLeft':
                        if (e.target.tagName !== 'TEXTAREA') {
                            e.preventDefault();
                            prevTask();
                        }
                        break;
                    case 'ArrowRight':
                        if (e.target.tagName !== 'TEXTAREA') {
                            e.preventDefault();
                            nextTask();
                        }
                        break;
                }
            }
        });
    </script>
</body>
</html>