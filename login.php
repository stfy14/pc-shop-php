<?php
require_once 'db.php';

session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Проверяем пароль через password_verify
    if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // === НАЧАЛО: МИГРАЦИЯ КОРЗИНЫ (COOKIE -> DB) ===
    require_once 'cart_core.php';
    
    // 1. Берем товары из куки (пока мы еще считаемся "гостем" для функции getCartIds, это сработает, но лучше прочитать куку напрямую)
    $cookieCart = json_decode($_COOKIE['cart_items'] ?? '[]', true);
    
    if (is_array($cookieCart) && count($cookieCart) > 0) {
        // 2. Добавляем их все в базу этому юзеру
        $sql = "INSERT IGNORE INTO cart (user_id, product_id) VALUES (?, ?)";
        $stmtInsert = $conn->prepare($sql);
        
        foreach ($cookieCart as $prodId) {
            $stmtInsert->execute([$user['id'], $prodId]);
        }
        
        // 3. Удаляем куку, чтобы не дублировать данные
        clearCookieCart();
    }
    // === КОНЕЦ МИГРАЦИИ ===

    header("Location: profile.php");
    exit;
    } else {
        $error = "Неверный логин или пароль";
    }
}

require_once 'header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">Вход</h3>
                
                <?php if($error): ?>
                    <div class="alert alert-danger py-2"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Логин</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small">Пароль</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">Войти</button>
                </form>
                
                <div class="text-center mt-3 small">
                    Нет аккаунта? <a href="register.php" class="text-decoration-none">Зарегистрироваться</a>
                </div>
            </div>
        </div>
    </div>
</div>

</div></body></html>