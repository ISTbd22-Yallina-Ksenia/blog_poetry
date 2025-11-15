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
    $comment_id = intval($_POST['comment_id']);
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action']; // 'like' или 'unlike'

    if (empty($comment_id)) {
        echo json_encode(['success' => false, 'message' => 'ID комментария не указан']);
        exit;
    }

    // Начинаем транзакцию
    $conn->begin_transaction();

    try {
        if ($action === 'like') {
            // Проверяем, не лайкнул ли уже пользователь этот комментарий
            $check_stmt = $conn->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
            $check_stmt->bind_param("ii", $comment_id, $user_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                throw new Exception('Вы уже лайкнули этот комментарий');
            }
            $check_stmt->close();

            // Добавляем запись о лайке
            $insert_stmt = $conn->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $comment_id, $user_id);
            $insert_stmt->execute();
            $insert_stmt->close();

            // Увеличиваем счетчик лайков в таблице comments
            $update_stmt = $conn->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ?");
            $update_stmt->bind_param("i", $comment_id);
            $update_stmt->execute();
            $update_stmt->close();

            $conn->commit();

            // Получаем обновленное количество лайков
            $count_stmt = $conn->prepare("SELECT likes FROM comments WHERE id = ?");
            $count_stmt->bind_param("i", $comment_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $likes_count = $count_result->fetch_assoc()['likes'];
            $count_stmt->close();

            echo json_encode([
                'success' => true, 
                'likes' => $likes_count,
                'action' => 'liked'
            ]);

        } else {
            // Убираем лайк
            $delete_stmt = $conn->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $comment_id, $user_id);
            $delete_stmt->execute();
            
            if ($delete_stmt->affected_rows === 0) {
                throw new Exception('Лайк не найден');
            }
            $delete_stmt->close();

            // Уменьшаем счетчик лайков в таблице comments
            $update_stmt = $conn->prepare("UPDATE comments SET likes = likes - 1 WHERE id = ?");
            $update_stmt->bind_param("i", $comment_id);
            $update_stmt->execute();
            $update_stmt->close();

            $conn->commit();

            // Получаем обновленное количество лайков
            $count_stmt = $conn->prepare("SELECT likes FROM comments WHERE id = ?");
            $count_stmt->bind_param("i", $comment_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $likes_count = $count_result->fetch_assoc()['likes'];
            $count_stmt->close();

            echo json_encode([
                'success' => true, 
                'likes' => $likes_count,
                'action' => 'unliked'
            ]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>