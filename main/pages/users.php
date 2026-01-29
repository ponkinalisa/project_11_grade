<?php
require_once '../php/config.php';

session_start();

if (!isset($_SESSION['login']) || $_SESSION['status'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $sql = "UPDATE users SET status = :status WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'status' => $new_status,
            'user_id' => $user_id
        ]);
        
        $message = "Статус успешно изменен";
    } catch (PDOException $e) {
        $message = "Ошибка: " . $e->getMessage();
    }
}

$search_query = '';
$users = [];

try {
    $sql = "SELECT id, login, surname, name, patronymic, status FROM users WHERE 1=1";
    $params = [];
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_query = trim($_GET['search']);
        $sql .= " AND (login LIKE :search 
                OR surname LIKE :search 
                OR name LIKE :search 
                OR patronymic LIKE :search)";
        $params['search'] = "%$search_query%";
    }
    
    $sql .= " ORDER BY surname, name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Ошибка: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link rel="stylesheet" type="text/css" href="../css/admin.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Управление пользователями</h1>
            <a href="admin.php" class="back-link">← Назад к админ-панели</a>
        </div>
        <?php if (isset($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="search-box">
            <form method="GET" class="search-form">
                <input type="text" name="search" 
                       placeholder="Поиск по имени, фамилии или логину..." 
                       class="search-input"
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn">Найти</button>
                <?php if ($search_query): ?>
                    <a href="users.php" class="btn btn-cancel">Сбросить</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="users-list">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <?php if ($search_query): ?>
                        Пользователи не найдены
                    <?php else: ?>
                        Нет пользователей в системе
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                <div class="user-item">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php 
                            $initials = mb_substr($user['name'], 0, 1) . mb_substr($user['surname'], 0, 1);
                            echo $initials;
                            ?>
                        </div>
                        <div>
                            <div class="user-name">
                                <?php 
                                $full_name = $user['surname'] . ' ' . $user['name'];
                                if (!empty($user['patronymic'])) {
                                    $full_name .= ' ' . $user['patronymic'];
                                }
                                echo htmlspecialchars($full_name);
                                ?>
                                <span class="user-status status-<?php echo $user['status']; ?>">
                                    <?php 
                                    $status_names = [
                                        'student' => 'Студент',
                                        'teacher' => 'Учитель',
                                        'admin' => 'Админ'
                                    ];
                                    echo $status_names[$user['status']];
                                    ?>
                                </span>
                            </div>
                            <div class="user-login">
                                Логин: <?php echo htmlspecialchars($user['login']); ?>
                            </div>
                        </div>
                    </div>
                    <button class="change-status-btn" 
                            onclick="openChangeStatusModal(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>', '<?php echo htmlspecialchars($user['surname'] . ' ' . $user['name']); ?>')">
                        Изменить статус
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Изменение статуса</h3>
            
            <div class="current-user" id="currentUserInfo"></div>
            
            <form method="post" id="statusForm">
                <input type="hidden" name="user_id" id="userId">
                
                <select name="new_status" class="status-select" id="statusSelect">
                    <option value="student">Студент</option>
                    <option value="teacher">Учитель</option>
                    <option value="admin">Администратор</option>
                </select>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">
                        Отмена
                    </button>
                    <button type="submit" name="change_status" class="btn btn-save">
                        Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openChangeStatusModal(userId, currentStatus, userName) {
            document.getElementById('userId').value = userId;
            document.getElementById('currentUserInfo').innerHTML = 
                `<strong>${userName}</strong> (текущий статус: <span class="user-status status-${currentStatus}">${getStatusName(currentStatus)}</span>)`;
            
            document.getElementById('statusSelect').value = currentStatus;
            
            document.getElementById('statusModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        function getStatusName(status) {
            const names = {
                'student': 'Студент',
                'teacher': 'Учитель',
                'admin': 'Администратор'
            };
            return names[status] || status;
        }
        
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>
</body>
</html>