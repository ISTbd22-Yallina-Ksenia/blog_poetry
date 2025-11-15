<?php
include_once("inc/db_connect.php");

header('Content-Type: application/json');

try {
    // Получаем все посты с именами авторов
    $posts = $pm_Blog->db->getAll("
        SELECT p.*, a.name as author_name 
        FROM posts p 
        LEFT JOIN authors a ON p.author_id = a.id 
        ORDER BY p.id DESC
    ");
    
    echo json_encode($posts);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>