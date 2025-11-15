<?php
session_start();
include('inc/db_connect.php');

header('Content-Type: application/json');

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $text = trim($_POST['text']);

    if (empty($text)) {
        echo json_encode(['success' => false, 'message' => 'Текст комментария не может быть пустым']);
        exit;
    }

    // Проверяем, это обычный комментарий или ответ
    if (isset($_POST['post_id'])) {
        // Обычный комментарий к посту
        $post_id = intval($_POST['post_id']);
        
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $user_id, $text);
    } elseif (isset($_POST['parent_comment_id'])) {
        // Ответ на комментарий
        $parent_comment_id = intval($_POST['parent_comment_id']);
        
        // Получаем post_id из родительского комментария
        $parent_stmt = $conn->prepare("SELECT post_id FROM comments WHERE id = ?");
        $parent_stmt->bind_param("i", $parent_comment_id);
        $parent_stmt->execute();
        $parent_result = $parent_stmt->get_result();
        $parent_comment = $parent_result->fetch_assoc();
        $parent_stmt->close();
        
        if (!$parent_comment) {
            echo json_encode(['success' => false, 'message' => 'Родительский комментарий не найден']);
            exit;
        }
        
        $post_id = $parent_comment['post_id'];
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, parent_id, text) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $post_id, $user_id, $parent_comment_id, $text);
    } else {
        echo json_encode(['success' => false, 'message' => 'Не указан пост или родительский комментарий']);
        exit;
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>