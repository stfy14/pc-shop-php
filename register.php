<?php
require_once 'db.php';

// Если уже вошел - кидаем в профиль
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Пароли не совпадают";
    } else {
        // Проверка: занят ли логин?
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Этот логин уже занят";
        } else {
            // Регистрируем (Хешируем пароль!)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            if ($stmt->execute([$username, $hashed_password])) {
                // Сразу авторизуем
                $_SESSION['user_id'] = $conn->lastInsertId();
                $_SESSION['role'] = 'user';
                $_SESSION['username'] = $username;
                header("Location: profile.php");
                exit;
            } else {
                $error = "Ошибка регистрации";
            }
        }
    }
}

require_once 'header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">Регистрация</h3>
                
                <?php if($error): ?>
                    <div class="alert alert-danger py-2"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Логин</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Пароль</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small">Повторите пароль</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">Создать аккаунт</button>
                </form>
                
                <div class="text-center mt-3 small">
                    Уже есть аккаунт? <a href="login.php" class="text-decoration-none">Войти</a>
                </div>
            </div>
        </div>
    </div>
</div>

</div></body></html>