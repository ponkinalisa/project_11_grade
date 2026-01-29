<?php
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) || $_SESSION['status'] == 'student'){
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['test_name'];
    $description = $_POST['test_description'];
    $time = $_POST['test_time'];
    $grade5 = $_POST['grade5'];
    $grade4 = $_POST['grade4'];
    $grade3 = $_POST['grade3'];


    $types_arr = array();
    $tasks_arr = array();

    $task = 1;
    $type = 1;
    $i = 0;

    foreach ($_POST as $value => $key) {
        print_r( $value);
        print_r( $key);
        print_r($type); 
        print_r($task);

        if ("count_type_" . ($type + 1) == $value){
            $type = $type + 1;
            $task = 1;
        }
        if ("count_type_" . $type == $value){
            $types_arr[$type] = ['count' => $key];
        }
        if ("type_" . $type . "_weight" == $value){
            $types_arr[$type] = array_merge(['weight' => $key], $types_arr[$type]);
        }
        if ("type_".$type."_task_".$task."_text" == $value){
            $tasks_arr[$i] = array('type' => $type - 1, 'text' => $key);
        }
        if ("type_".$type."_task_".$task."_answer" == $value){
            $tasks_arr[$i] = array_merge(['answer' => $key], $tasks_arr[$i]);
            $file = $_FILES["type_".$type."_task_".$task."_image"] ?? null;
            print_r($file);
            if ($file and $file['error'] == 0){
                print_r(0);
                $type_f = $file['type'];
                $file_name = $file['name'];
                $tmp_name = $file["tmp_name"];
                $file_name_sep = mb_split("\.", $file_name);
                $error = 'Неподдерживаемый формат изображения.';
                $new_file_name = random_int(1, 10000000000);
                $ext = $file_name_sep[count($file_name_sep)-1];
                switch ($type_f) {
                    case 'image/jpg':
                    case 'image/jpeg':
                        $error = Null;
                        break;
                    case 'image/png':
                        $error = Null;
                        break;
                    }
                if (!$error){
                    $dir_name = $_SESSION['login'];
                    $directory = "../user_img/$dir_name";
                    if (!file_exists($directory)) {
                        mkdir($directory);  
                    }
                    move_uploaded_file($tmp_name, "./../user_img/$dir_name/$new_file_name.$ext");
                    $path = "../user_img/$dir_name" . "/" . $new_file_name . '.' . $ext;
                    $tasks_arr[$i] = array_merge(['path' => $path], $tasks_arr[$i]);
                    print_r($path);
                }else{
                    die($error);
                }
            }
            $task = $task + 1;
            $i += 1;
        }
    }

    $count_tasks = 0;
    foreach ($types_arr as $c){
        $count_tasks += $c['count'];
    }
try {
    $sql = "INSERT INTO tests (author_id, name, description, time, grade5, grade4, grade3, count_tasks, is_active) VALUES (:author_id, :name, :description, :time, :grade5, :grade4, :grade3, :count_tasks, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['author_id' => $_SESSION['id'], 'name' => $name, 'description' => $description, 'time' => $time, 'grade5' => $grade5, 'grade4' => $grade4, 'grade3' => $grade3, 'count_tasks' => $count_tasks]);
}catch (PDOException $e) {  
    echo 'ошибка!' . $e->getMessage(); 
}  
try{
    $test_id = $pdo->lastInsertId();
    $types_ids = array();

    foreach ($types_arr as $type){
        $sql = "INSERT INTO types (test_id, amount, score) VALUES (:test_id, :count, :score)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id, 'count' => $type['count'], 'score' => $type['weight']]);
        $i = $pdo->lastInsertId();
        array_push($types_ids, $i);
    }
    foreach ($tasks_arr as $task){
        if (isset($task['path'])){
            $path = $task['path'];
        }else{
            $path = '';
        }
        $sql = "INSERT INTO tasks (test_id, type_id, text, answer, path_to_img) VALUES (:test_id, :type_id, :text, :answer, :path)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['test_id' => $test_id, 'type_id' => $types_ids[$task['type']], 'text' => $task['text'], 'answer' => $task['answer'], 'path' => $path]);
    }
    header('Location: teacher_main.php');
    exit;
}catch (Exception $e) {  
    echo 'ошибка!' . $e->getMessage();  
}
}
?>




<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание теста | Образовательная платформа</title>
    <link rel="stylesheet" type="text/css" href="../css/new_test.css">
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
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Создание нового теста</h1>
                <a href="teacher_main.php" class="back-btn">← Назад к тестам</a>
            </div>
            
            <div class="form-container">
                <form enctype="multipart/form-data" action="teacher_new_test.php" method="post">
                <div class="form-section">
                    <h2 class="section-title">
                        <span class="section-title-icon">📝</span>
                        Основная информация
                    </h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="testName">Название теста *</label>
                            <input type="text" id="testName" placeholder="Введите название теста" name="test_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="testDescription">Описание теста</label>
                        <textarea id="testDescription" placeholder="Опишите содержание теста, его цели и задачи" name="test_description"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="testTime">Время на выполнение (минут) *</label>
                        <input type="number" id="testTime" min="1" max="180" value="45" name="test_time" required>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2 class="section-title">
                        <span class="section-title-icon">📊</span>
                        Критерии оценки
                    </h2>
                    
                    <p style="margin-bottom: 20px; color: var(--text-secondary);">
                        Укажите минимальный процент выполнения для каждой оценки по 5-балльной шкале
                    </p>
                    
                    <div class="criteria-grid">
                        <div class="criteria-item">
                            <div class="criteria-label">Оценка "5"</div>
                            <div class="criteria-input">
                                <input type="number" id="grade5" min="0" max="100" value="85" name="grade5">
                                <span>%</span>
                            </div>
                        </div>
                        
                        <div class="criteria-item">
                            <div class="criteria-label">Оценка "4"</div>
                            <div class="criteria-input">
                                <input type="number" id="grade4" min="0" max="100" value="65" name="grade4">
                                <span>%</span>
                            </div>
                        </div>
                        
                        <div class="criteria-item">
                            <div class="criteria-label">Оценка "3"</div>
                            <div class="criteria-input">
                                <input type="number" id="grade3" min="0" max="100" value="45" name="grade3">
                                <span>%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                        Оценка "2" выставляется автоматически при результате ниже <span id="grade2Value">45%</span>
                    </div>
                </div>
                <div class="form-section">
                    <h2 class="section-title">
                        <span class="section-title-icon">🔧</span>
                        Структура теста
                    </h2>
                    
                    <p style="margin-bottom: 20px; color: var(--text-secondary);">
                        Добавьте типы заданий и наполните их вопросами
                    </p>
                    
                    <div class="task-types" id="taskTypes">
                        <div class="task-type-card">
                            <div class="task-type-header">
                                <div class="task-type-title">Тип задания 1</div>
                                <div class="task-type-controls">
                                    <div class="task-weight">
                                        <label for="taskWeight1">Количество заданий этого типа в тесте:</label>
                                        <input type="number" id="taskWeight1" min="1" value="1" name="count_type_1" class="count">
                                    </div>
                                    <div class="icon-btn delete-btn" onclick="deleteTaskType(this, 1)">🗑️</div>
                                </div>
                            </div>
                            
                            <div class="task-weight">
                                <label for="taskWeight1">Вес в баллах:</label>
                                <input type="number" id="taskWeight1" min="1" value="1" name="type_1_weight">
                            </div>
                            
                            <div class="tasks-list">
                                <div class="task-item">
                                    <div class="task-header">
                                        <div class="task-number">Задание 1</div>
                                        <div class="task-type-controls">
                                            <div class="icon-btn delete-btn" onclick="deleteTask(this, 1)">🗑️</div>
                                        </div>
                                    </div>
                                    
                                    <div class="task-content">
                                        <div class="form-group">
                                            <label>Текст задания</label>
                                            <textarea placeholder="Введите текст задания" name="type_1_task_1_text" required></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Правильный ответ</label>
                                            <input  type="number" step="any" placeholder="Введите правильный ответ" name="type_1_task_1_answer" required></input>
                                        </div>
                                        
                                        <div class="image-upload">
                                            <label>Изображение к заданию (опционально)</label>
                                            <input type="file" accept="image/png, image/jpg, image/jpeg" onchange="previewImage(this)" name="type_1_task_1_image">
                                            <img class="image-preview" src="" alt="Предпросмотр" style="display:none;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="add-task-btn" onclick="addTask(this)">+ Добавить задание</div>
                        </div>
                    </div>
                    
                    <div class="add-type-btn" onclick="addTaskType()">
                        <span>+</span>
                        <span>Добавить тип задания</span>
                    </div>
                </div>

                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                    Итого заданий в тесте: <span id="countTasks">1</span>
                </div>
                <div class="form-actions">
                    <button class="cancel-btn">Отмена</button>
                    <button class="save-btn" type="submit">Сохранить тест</button>
                </div>
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
        let taskTypeCount = 1;
        let taskCounts = {1: 1};
        
        function updateGrade2Value() {
            const grade3Value = document.getElementById('grade3').value;
            document.getElementById('grade2Value').textContent = grade3Value + '%';
        }

        function updateCount(){
            let a = 0;
            let arr = document.getElementsByClassName('count');
            for (let i = 0; i < arr.length; i++){
                a = a + Number(arr[i].value);
            }
            document.getElementById('countTasks').innerText = String(a);
        }
        
        function previewImage(input) {
            const preview = input.parentElement.querySelector('.image-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function addTaskType() {
            taskTypeCount++;
            taskCounts[taskTypeCount] = 1;
            
            const taskTypesContainer = document.getElementById('taskTypes');
            const newTaskType = document.createElement('div');
            newTaskType.className = 'task-type-card';
            newTaskType.innerHTML = `
                <div class="task-type-header">
                                <div class="task-type-title">Тип задания ${taskTypeCount}</div>
                                <div class="task-type-controls">
                                    <div class="task-weight">
                                        <label for="taskWeight1">Количество заданий этого типа в тесте:</label>
                                        <input type="number" id="taskWeight1" min="1" value="1" name="count_type_${taskTypeCount}" class="count">
                                    </div>
                                    <div class="icon-btn delete-btn" onclick="deleteTaskType(this, ${taskTypeCount})">🗑️</div>
                                </div>
                            </div>
                            
                            <div class="task-weight">
                                <label for="taskWeight1">Вес в баллах:</label>
                                <input type="number" id="taskWeight1" min="1" value="1" name="type_${taskTypeCount}_weight">
                            </div>
                            
                            <div class="tasks-list">
                                <!-- Задания -->
                                <div class="task-item">
                                    <div class="task-header">
                                        <div class="task-number">Задание 1</div>
                                        <div class="task-type-controls">
                                            <div class="icon-btn delete-btn" onclick="deleteTask(this, 1)">🗑️</div>
                                        </div>
                                    </div>
                                    
                                    <div class="task-content">
                                        <div class="form-group">
                                            <label>Текст задания</label>
                                            <textarea placeholder="Введите текст задания" name="type_${taskTypeCount}_task_1_text" required></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Правильный ответ</label>
                                            <input  type="number" step="any" placeholder="Введите правильный ответ" name="type_${taskTypeCount}_task_1_answer" required></input>
                                        </div>
                                        
                                        <div class="image-upload">
                                            <label>Изображение к заданию (опционально)</label>
                                            <input type="file" accept="image/png, image/jpg, image/jpeg" onchange="previewImage(this)" name="type_${taskTypeCount}_task_1_image">
                                            <img class="image-preview" src="" alt="Предпросмотр" style="display:none;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="add-task-btn" onclick="addTask(this)">+ Добавить задание</div>
            `;
            
            taskTypesContainer.appendChild(newTaskType);
            updateCount();
        }
        
        function deleteTaskType(button, n) {
            if (document.querySelectorAll('.task-type-card').length > 1) {
                for (let i = n; i < document.querySelectorAll('.task-type-card').length; i++) {
                    document.getElementsByClassName("task-type-card")[i].innerHTML = document.getElementsByClassName("task-type-card")[i].innerHTML.replace('Тип задания ' + (i + 1), 'Тип задания ' + i);
                    document.getElementsByClassName("task-type-card")[i].innerHTML = document.getElementsByClassName("task-type-card")[i].innerHTML.replace('deleteTaskType(this, ' + (i + 1) + ')', 'deleteTaskType(this, ' + i + ')');
                    document.getElementsByClassName("task-type-card")[i].innerHTML = document.getElementsByClassName("task-type-card")[i].innerHTML.replace('type_' + (i + 1), 'type_' + i);
                }
                button.closest('.task-type-card').remove();
                taskTypeCount--;
            } else {
                alert('Должен остаться хотя бы один тип задания');
            }
            updateCount();
        }
        
        function addTask(button) {
            const taskTypeCard = button.closest('.task-type-card');
            const taskTypeHeader = taskTypeCard.querySelector('.task-type-title');
            const taskTypeNumber = taskTypeHeader.textContent.match(/\d+/)[0];
            
            taskCounts[taskTypeNumber]++;
            const taskNumber = taskCounts[taskTypeNumber];
            
            const tasksList = taskTypeCard.querySelector('.tasks-list');
            const newTask = document.createElement('div');
            newTask.className = 'task-item';
            newTask.innerHTML = `
                                    <div class="task-header">
                                        <div class="task-number">Задание ${taskNumber}</div>
                                        <div class="task-type-controls">
                                            <div class="icon-btn delete-btn" onclick="deleteTask(this, ${taskNumber})">🗑️</div>
                                        </div>
                                    </div>
                                    
                                    <div class="task-content">
                                        <div class="form-group">
                                            <label>Текст задания</label>
                                            <textarea placeholder="Введите текст задания" name="type_${taskTypeNumber}_task_${taskNumber}_text" required></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Правильный ответ</label>
                                            <input  type="number" step="any" placeholder="Введите правильный ответ" name="type_${taskTypeNumber}_task_${taskNumber}_answer" required></input>
                                        </div>
                                        
                                        <div class="image-upload">
                                            <label>Изображение к заданию (опционально)</label>
                                            <input type="file" accept="image/png, image/jpg, image/jpeg" onchange="previewImage(this)" name="type_${taskTypeNumber}_task_${taskNumber}_image">
                                            <img class="image-preview" src="" alt="Предпросмотр" style="display:none;">
                                        </div>
                                    </div>
            `;
            
            tasksList.appendChild(newTask);
        }
        
        function deleteTask(button, n) {
            const taskItem = button.closest('.task-item');
            const tasksList = taskItem.parentElement;
            
            if (tasksList.querySelectorAll('.task-item').length > 1) {
                for (let i = n; i < tasksList.querySelectorAll('.task-item').length; i++) {
                    tasksList.getElementsByClassName("task-item")[i].innerHTML = tasksList.getElementsByClassName("task-item")[i].innerHTML.replace('Задание ' + (i + 1), 'Задание ' + i);
                    tasksList.getElementsByClassName("task-item")[i].innerHTML = tasksList.getElementsByClassName("task-item")[i].innerHTML.replace('deleteTask(this, ' + (i + 1) + ')', 'deleteTask(this, ' + i + ')');
                    tasksList.getElementsByClassName("task-item")[i].innerHTML = tasksList.getElementsByClassName("task-item")[i].innerHTML.replace('task_' + (i + 1), 'task_' + i);
                }
                taskItem.remove();
                const taskTypeHeader = tasksList.parentElement.querySelector('.task-type-title');
                const taskTypeNumber = taskTypeHeader.textContent.match(/\d+/)[0];
               
                taskCounts[taskTypeNumber]--;
            } else {
                alert('Должно остаться хотя бы одно задание в типе');
            }
        }
        

        document.addEventListener('DOMContentLoaded', function() {
            updateGrade2Value();
            document.getElementById('grade3').addEventListener('input', updateGrade2Value);
            document.querySelector('.save-btn').addEventListener('click', function() {
                alert('Тест успешно сохранен!');
            });
            document.querySelector('.cancel-btn').addEventListener('click', function() {
                if (confirm('Вы уверены, что хотите отменить создание теста? Все несохраненные данные будут потеряны.')) {
                    window.history.back();
                }
            });
        });
        document.addEventListener('change',  updateCount);
    </script>
</body>
</html>