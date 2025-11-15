<?php
session_start();
include('inc/db_connect.php');

header('Content-Type: application/json');

// Проверяем, авторизован ли пользователь и имеет ли роль "user"
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'У вас нет прав для публикации постов']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $text = trim($_POST['text']);
    $user_id = $_SESSION['user_id'];

    if (empty($text)) {
        echo json_encode(['success' => false, 'message' => 'Текст поста не может быть пустым']);
        exit;
    }

    // Вставляем пост с ID текущего пользователя
    $stmt = $conn->prepare("INSERT INTO posts (text, author_id) VALUES (?, ?)");
    $stmt->bind_param("si", $text, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>