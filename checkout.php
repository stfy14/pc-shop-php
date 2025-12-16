<?php
require_once 'db.php';
require_once 'cart_core.php'; // Чтобы проверить, не пустая ли корзина

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Проверка авторизации
if (!isset($_SESSION['user_id'])) {
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
    <div class="col-md-6">
        <!-- ИЗМЕНЕНО: Полностью переработанная карточка -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-lg-5">
                <h2 class="fw-bold mb-3">Оформление заказа</h2>
                
                <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 mb-4">
                    <span class="text-muted">Всего товаров: <strong><?php echo count($cartIds); ?> шт.</strong></span>
                    <div class="text-end">
                        <span class="fs-2 fw-bold text-primary"><?php echo number_format($total, 0, '', ' '); ?> ₽</span>
                    </div>
                </div>

                <hr class="text-muted opacity-25 mb-4">

                <form action="place_order.php" method="post">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1"><i class="bi bi-geo-alt-fill me-1"></i> Адрес доставки</label>
                        <textarea name="address" class="form-control form-control-lg bg-light border-0 rounded-3" rows="3" required placeholder="Город, улица, дом, квартира..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1"><i class="bi bi-telephone-fill me-1"></i> Телефон</label>
                        <input type="tel" name="phone" id="phone" class="form-control form-control-lg bg-light border-0 rounded-3" required placeholder="+7 (999) 000-00-00" maxlength="18">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow-sm">Подтвердить заказ</button>
                    </div>
                </form>

                <!-- СКРИПТ МАСКИ ТЕЛЕФОНА (обновлен под новый формат) -->
                <script>
                document.getElementById('phone').addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    let formattedValue = '+';
                    
                    if (value.length > 0) {
                        formattedValue += value.substring(0,1);
                        if (value.length > 1) {
                            formattedValue += ' (' + value.substring(1,4);
                        }
                        if (value.length > 4) {
                            formattedValue += ') ' + value.substring(4,7);
                        }
                        if (value.length > 7) {
                            formattedValue += '-' + value.substring(7,9);
                        }
                        if (value.length > 9) {
                            formattedValue += '-' + value.substring(9,11);
                        }
                    }
                    e.target.value = formattedValue;
                });
                </script>
            </div>
        </div>
    </div>
</div>

</div></body></html>