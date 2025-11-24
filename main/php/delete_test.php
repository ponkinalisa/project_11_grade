<?php 
require_once 'config.php';

session_start();

if (!isset($_SESSION['login'])){
    header('Location: ../../index.php');
    exit;
}

$test_id = $_GET['test_id'] ?? null;
try{

$sql = "DELETE FROM tasks WHERE test_id = :test_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['test_id' => $test_id]);

$sql = "DELETE FROM types WHERE test_id = :test_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['test_id' => $test_id]);

$sql = "DELETE FROM test_results WHERE test_id = :test_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['test_id' => $test_id]);

$sql = "DELETE FROM tests WHERE id = :test_id AND author_id = :author_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['test_id' => $test_id, 'author_id' => $_SESSION['id']]);

header('Location: ../pages/teacher_main.php');
exit;

}catch(PDOException $e){
    echo($e->getMessage());
}
?>