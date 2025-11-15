<?php
session_start();
include('inc/db_connect.php');

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Подготовленный запрос для защиты от SQL-инъекций
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Перенаправляем на lenta.php после успешной авторизации
        header('Location: lenta.php');
        exit();
    } else {
        $error_message = "Неверный логин или пароль";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link rel="stylesheet" type="text/css" href="assets/css/style_enter_1v.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2 class="title-logo">VerseShare</h2>
            <p>Введите ваши данные для входа</p>
        </div>

        <?php if ($error_message): ?>
        <div class="error-message" id="errorMessage">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" required placeholder="Введите ваш логин" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required placeholder="Введите ваш пароль">
            </div>
            
            <button type="submit" class="btn-login">Войти</button>
        </form>

        <div class="additional-links">
            <a href="register.php">Нет аккаунта? Зарегистрируйтесь</a>
        </div>
    </div>

    <script>
        // Автоматическое скрытие сообщения об ошибке через 5 секунд
        <?php if ($error_message): ?>
        setTimeout(function() {
            const errorElement = document.getElementById('errorMessage');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>