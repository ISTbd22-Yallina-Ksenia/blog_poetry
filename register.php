<?php
session_start();
include('inc/db_connect.php');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Проверяем обязательные поля
    if (empty($username) || empty($password)) {
        $error_message = "Все поля обязательны для заполнения";
    }
    // Проверяем длину пароля
    elseif (strlen($password) < 8) {
        $error_message = "Пароль должен содержать не менее 8 символов";
    }
    // Проверяем существование пользователя
    else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $error_message = "Пользователь с таким именем уже существует";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Хешируем пароль и создаем нового пользователя
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);
            
            if ($stmt->execute()) {
                // Автоматически авторизуем пользователя после регистрации
                $user_id = $stmt->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user'; // или какая роль по умолчанию у вас
                
                $stmt->close();
                
                // Перенаправляем на lenta.php
                header('Location: lenta.php');
                exit();
            } else {
                $error_message = "Ошибка при регистрации: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" type="text/css" href="assets/css/style_register_1v.css">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2 class="title-logo">VerseShare</h2>
            <p>Заполните данные для регистрации</p>
        </div>

        <?php if ($error_message): ?>
        <div class="error-message" id="errorMessage">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="success-message" id="successMessage">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Введите имя пользователя" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Введите пароль">
                <div class="password-requirements">Пароль должен содержать не менее 8 символов</div>
            </div>
            
            <button type="submit" class="btn-register">Зарегистрироваться</button>
        </form>

        <div class="additional-links">
            <a href="login.php">Уже есть аккаунт? Войдите</a>
        </div>
    </div>

    <script>
        // Автоматическое скрытие сообщений через 5 секунд
        <?php if ($error_message): ?>
        setTimeout(function() {
            const errorElement = document.getElementById('errorMessage');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }, 5000);
        <?php endif; ?>

        <?php if ($success_message): ?>
        setTimeout(function() {
            const successElement = document.getElementById('successMessage');
            if (successElement) {
                successElement.style.display = 'none';
            }
        }, 5000);
        <?php endif; ?>

        // Динамическая проверка пароля
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const requirements = this.parentNode.querySelector('.password-requirements');
            
            if (password.length < 8) {
                requirements.style.color = '#d63031';
            } else {
                requirements.style.color = '#666';
            }
        });
    </script>
</body>
</html>