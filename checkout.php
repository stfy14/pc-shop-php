<?php
require_once 'db.php';
require_once 'cart_core.php'; // Чтобы проверить, не пустая ли корзина

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    // Запоминаем, куда он хотел попасть, чтобы вернуть после входа (можно доработать login.php)
    header("Location: login.php");
    exit;
}

// 2. Проверка корзины
$cartIds = getCartIds();
if (empty($cartIds)) {
    header("Location: index.php");
    exit;
}

// Считаем сумму для отображения
$ids_string = implode(',', array_map('intval', $cartIds));
$stmt = $conn->query("SELECT SUM(price) FROM products WHERE id IN ($ids_string)");
$total = $stmt->fetchColumn();

require_once 'header.php';
?>

<div class="row justify-content-center mt-4">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Оформление заказа</h4>
            </div>
            <div class="card-body p-4">
                <p class="text-muted">Всего товаров: <strong><?php echo count($cartIds); ?></strong></p>
                <h3 class="mb-4">К оплате: <?php echo number_format($total, 0, '', ' '); ?> ₽</h3>

                <form action="place_order.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">Адрес доставки</label>
                        <textarea name="address" class="form-control" rows="3" required placeholder="Город, улица, дом..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Телефон</label>
                        <!-- Добавили id="phone" и maxlength -->
                        <input type="tel" name="phone" id="phone" class="form-control" required placeholder="+7-999-000-0000" maxlength="16">
                        <div class="form-text text-muted">Формат: +x-xxx-xxx-xxxx</div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fs-5">Подтвердить заказ</button>
                </form>

                <!-- СКРИПТ МАСКИ ТЕЛЕФОНА -->
                <script>
                document.getElementById('phone').addEventListener('input', function (e) {
                    let x = e.target.value.replace(/\D/g, '') // Удаляем всё, кроме цифр
                        .match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,4})/); // Разбиваем на группы

                    if (!x[1]) {
                        e.target.value = '+'; // Если пусто, ставим плюс
                        return;
                    }

                    // Собираем строку по шаблону +x-xxx-xxx-xxxx
                    e.target.value = !x[2] ? '+' + x[1] : '+' + x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '') + (x[4] ? '-' + x[4] : '');
                });
                </script>
            </div>
        </div>
    </div>
</div>

</div></body></html>