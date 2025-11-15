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
    $post_id = intval($_POST['post_id']);
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action']; // 'like' или 'unlike'

    if (empty($post_id)) {
        echo json_encode(['success' => false, 'message' => 'ID поста не указан']);
        exit;
    }

    // Проверяем, существует ли пост
    $check_stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
    $check_stmt->bind_param("i", $post_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if (!$result->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Пост не найден']);
        exit;
    }

    if ($action === 'like') {
        // Добавляем лайк
        $insert_stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $post_id, $user_id);
        
        if ($insert_stmt->execute()) {
            // Получаем обновленное количество лайков
            $count_stmt = $conn->prepare("SELECT COUNT(*) as likes_count FROM post_likes WHERE post_id = ?");
            $count_stmt->bind_param("i", $post_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $likes_count = $count_result->fetch_assoc()['likes_count'];
            
            echo json_encode([
                'success' => true, 
                'likes' => $likes_count,
                'action' => 'liked'
            ]);
        } else {
            // Если ошибка из-за дубликата (уже лайкнул)
            if ($conn->errno === 1062) {
                echo json_encode(['success' => false, 'message' => 'Вы уже лайкнули этот пост']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $conn->error]);
            }
        }
    } else {
        // Убираем лайк
        $delete_stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $post_id, $user_id);
        
        if ($delete_stmt->execute()) {
            // Получаем обновленное количество лайков
            $count_stmt = $conn->prepare("SELECT COUNT(*) as likes_count FROM post_likes WHERE post_id = ?");
            $count_stmt->bind_param("i", $post_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $likes_count = $count_result->fetch_assoc()['likes_count'];
            
            echo json_encode([
                'success' => true, 
                'likes' => $likes_count,
                'action' => 'unliked'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $conn->error]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>