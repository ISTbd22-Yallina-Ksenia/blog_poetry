<?php 
include_once("inc/db_connect.php");
session_start(); // Запускаем сессию для всех

// Проверяем, авторизован ли пользователь
$is_auth = isset($_SESSION['user_id']);
$user_id = $is_auth ? $_SESSION['user_id'] : 0;
$username = $is_auth ? $_SESSION['username'] : null;
$role = $is_auth ? $_SESSION['role'] : null;

// Получаем посты с именами пользователей и информацией о лайках
$posts_query = "
    SELECT 
        p.*, 
        u.username as author_name,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
        " . ($is_auth ? "(SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = $user_id) as user_liked" : "0 as user_liked") . "
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id 
    ORDER BY p.id DESC
";

$posts = $conn->query($posts_query);

// Для каждого поста получаем комментарии
$posts_data = [];
if ($posts && $posts->num_rows > 0) {
    while ($post = $posts->fetch_assoc()) {
        // Получаем комментарии для этого поста
        $comments_stmt = $conn->prepare("
            SELECT c.*, u.username 
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? 
            ORDER BY c.time ASC
        ");
        $comments_stmt->bind_param("i", $post['id']);
        $comments_stmt->execute();
        $comments_result = $comments_stmt->get_result();
        
        $comments = [];
        while ($comment = $comments_result->fetch_assoc()) {
            // Для каждого комментария проверяем лайки (только для авторизованных)
            if ($is_auth) {
                $check_comment_like_stmt = $conn->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
                $check_comment_like_stmt->bind_param("ii", $comment['id'], $user_id);
                $check_comment_like_stmt->execute();
                $check_comment_like_stmt->store_result();
                $comment['user_liked'] = $check_comment_like_stmt->num_rows > 0;
                $check_comment_like_stmt->close();
            } else {
                $comment['user_liked'] = false;
            }
            
            $comments[] = $comment;
        }
        $comments_stmt->close();
        
        $post['comments'] = $comments;
        $posts_data[] = $post;
    }
}

// Проверяем, может ли пользователь публиковать посты (только авторизованные с ролью user)
$can_post = $is_auth && $role === 'user';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лента микроблога</title>
    <link rel="stylesheet" type="text/css" href="assets/css/style_lenta_2v.css">
</head>
<body>
    <header>
        <div>
            <h1 class="title-logo">VerseShare</h1>
        </div>
        <div class="user-info">
            <?php if ($is_auth): ?>
                Добро пожаловать, <?php echo htmlspecialchars($username); ?>!
                <a href='logout.php'>Выйти</a>
            <?php else: ?>
                <a href='login.php'>Войти</a> | <a href='register.php'>Зарегистрироваться</a>
            <?php endif; ?>
        </div>
    </header>
    
    <?php if ($can_post): ?>
    <!-- Кнопка для открытия модального окна -->
    <button class="add-post-btn" id="addPostBtn">
        ✏️ Добавить пост
    </button>
    
    <!-- Модальное окно для создания поста -->
    <div id="postModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Новая запись</h3>
                <button class="close-btn" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <textarea class="post-textarea" id="postText" placeholder="Поделитесь своими мыслями..."></textarea>
                <button class="publish-btn" id="publishBtn">Опубликовать</button>
            </div>
        </div>
    </div>
    <?php elseif ($is_auth): ?>
    <div class="auth-prompt">
        У вас нет прав для публикации постов. Только пользователи с ролью "user" могут публиковать записи.
    </div>
    <?php endif; ?>
    
    <div id="postsContainer">
        <?php if (count($posts_data) > 0): ?>
            <?php foreach ($posts_data as $post): ?>
                <div class="post" data-post-id="<?php echo $post['id']; ?>">
                    <div class="post-header">
                        <span class="author-name">
                            <?php echo htmlspecialchars($post['author_name'] ?? 'Аноним'); ?>
                        </span>
                        <span class="post-date">
                            <?php 
                            if (!empty($post['time'])) {
                                $date = new DateTime($post['time']);
                                echo $date->format('d.m.Y H:i');
                            } else {
                                echo 'Дата не указана';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['text'])); ?>
                    </div>
                    <?php if (!empty($post['file'])): ?>
                        <div class="post-image">
                            <img src="<?php echo htmlspecialchars($post['file']); ?>" alt="Изображение поста">
                        </div>
                    <?php endif; ?>
                    <div class="post-actions">
                        <?php if ($is_auth): ?>
                            <button class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                    data-post-id="<?php echo $post['id']; ?>"
                                    data-liked="<?php echo $post['user_liked'] ? '1' : '0'; ?>">
                                ❤️ <span class="like-count"><?php echo $post['likes_count'] ?? 0; ?></span>
                            </button>
                        <?php else: ?>
                            <span class="like-btn disabled-action">
                                ❤️ <span class="like-count"><?php echo $post['likes_count'] ?? 0; ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="comment-section">
                        <h4>Комментарии</h4>
                        <div class="comments-list" id="comments-<?php echo $post['id']; ?>">
                            <?php foreach ($post['comments'] as $comment): ?>
                                <?php 
                                $comment_class = $comment['parent_id'] ? 'comment reply' : 'comment';
                                ?>
                                <div class="<?php echo $comment_class; ?>" data-comment-id="<?php echo $comment['id']; ?>">
                                    <div class="comment-header">
                                        <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                        <span class="comment-date">
                                            <?php 
                                            $date = new DateTime($comment['time']);
                                            echo $date->format('d.m.Y H:i');
                                            ?>
                                        </span>
                                    </div>
                                    <div class="comment-text">
                                        <?php echo nl2br(htmlspecialchars($comment['text'])); ?>
                                    </div>
                                    <div class="comment-actions">
                                        <?php if ($is_auth): ?>
                                            <button class="comment-like-btn <?php echo $comment['user_liked'] ? 'liked' : ''; ?>" 
                                                    data-comment-id="<?php echo $comment['id']; ?>"
                                                    data-liked="<?php echo $comment['user_liked'] ? '1' : '0'; ?>">
                                                ❤️ <span class="comment-like-count"><?php echo $comment['likes'] ?? 0; ?></span>
                                            </button>
                                            <button class="reply-btn" data-comment-id="<?php echo $comment['id']; ?>">Ответить</button>
                                        <?php else: ?>
                                            <span class="comment-like-btn disabled-action">
                                                ❤️ <span class="comment-like-count"><?php echo $comment['likes'] ?? 0; ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($is_auth): ?>
                                        <div class="reply-form" id="reply-form-<?php echo $comment['id']; ?>">
                                            <div class="comment-input">
                                                <textarea type="text" placeholder="Введите ответ..." id="reply-input-<?php echo $comment['id']; ?>"></textarea>
                                                <button class="reply-submit-btn" data-comment-id="<?php echo $comment['id']; ?>">Отправить</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($is_auth): ?>
                            <div class="comment-input">
                                <textarea type="text" placeholder="Добавить комментарий..." id="comment-input-<?php echo $post['id']; ?>"></textarea>
                                <button class="comment-btn" data-post-id="<?php echo $post['id']; ?>">Отправить</button>
                            </div>
                        <?php else: ?>
                            <div class="auth-prompt">
                                <a href="login.php">Войдите</a>, чтобы оставлять комментарии
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-posts">
                Пока нет постов. Будьте первым, кто поделится своими мыслями!
            </div>
        <?php endif; ?>
    </div>

<?php 
// Включаем скрипты только для авторизованных пользователей
if ($is_auth) {
    include("inc/scripts_lenta.php");
} else {
    // Минимальные скрипты для просмотра (если нужны)
    echo '<script>console.log("Гость: просмотр ленты");</script>';
}
?>
</body>
</html>