<?php
require_once 'db.php';
require_once 'header.php';

// Проверка Админа
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit;

$uuid = $_GET['uid'];

// 1. Получаем заказ + имя пользователя
$sql = "SELECT orders.*, users.username, users.id as user_id 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        WHERE orders.uuid = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$uuid]);
$order = $stmt->fetch();

if (!$order) die('Заказ не найден');

// 2. Обработка смены статуса
if (isset($_POST['new_status'])) {
    $conn->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$_POST['new_status'], $order['id']]);
    echo "<script>window.location.href='admin_order_view.php?uid=$uuid';</script>";
}

// 3. Обработка ответа в чат
if (isset($_POST['reply_message'])) {
    $msg = trim($_POST['reply_message']);
    if (!empty($msg)) {
        $conn->prepare("INSERT INTO order_messages (order_id, sender_role, message) VALUES (?, 'admin', ?)")
             ->execute([$order['id'], $msg]);
        echo "<script>window.location.href='admin_order_view.php?uid=$uuid';</script>";
    }
}

// Получаем товары
$stmtItems = $conn->prepare("SELECT p.id as product_id, p.title, p.image, oi.price_at_purchase 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
$stmtItems->execute([$order['id']]);
$items = $stmtItems->fetchAll();

// Получаем чат
$stmtChat = $conn->prepare("SELECT * FROM order_messages WHERE order_id = ? ORDER BY created_at ASC");
$stmtChat->execute([$order['id']]);
$messages = $stmtChat->fetchAll();

// Статусы для бейджа
$statusMap = [
    'new' => ['Новый', 'bg-primary'],
    'processing' => ['В работе', 'bg-warning text-dark'],
    'shipped' => ['Отправлен', 'bg-success'],
    'cancelled' => ['Отмена (Маг.)', 'bg-danger'],
    'cancelled_by_user' => ['Отмена (Клиент)', 'bg-secondary']
];
$st = $statusMap[$order['status']] ?? [$order['status'], 'bg-secondary'];
?>

<div class="row justify-content-center mt-4 mb-5">
    
    <!-- ЗАГОЛОВОК С КНОПКОЙ НАЗАД -->
    <div class="col-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <a href="admin_orders.php" class="btn btn-white border shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="bi bi-arrow-left text-dark"></i>
                </a>
                <div>
                    <h4 class="fw-bold mb-0">Управление заказом</h4>
                    <span class="text-muted small font-monospace">UUID: <?php echo substr($order['uuid'], 0); ?></span>
                </div>
            </div>
            <!-- Текущий статус -->
            <span class="badge rounded-pill <?php echo $st[1]; ?> fs-6 px-3 py-2"><?php echo $st[0]; ?></span>
        </div>
    </div>

    <!-- ЛЕВАЯ ЧАСТЬ: Данные и Товары -->
    <div class="col-lg-8">
        
        <!-- КАРТОЧКА УПРАВЛЕНИЯ -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold">Детали заказа #<?php echo $order['id']; ?></h5>
            </div>
            <div class="card-body p-4">
                <!-- Смена статуса -->
                <form method="post" class="row g-3 align-items-end mb-4 bg-light p-3 rounded-3 mx-0">
                    <div class="col-md-8">
                        <label class="form-label text-muted small fw-bold text-uppercase">Изменить статус</label>
                        <select name="new_status" class="form-select border-0 shadow-sm">
                            <option value="new" <?php if($order['status']=='new') echo 'selected'; ?>>Новый</option>
                            <option value="processing" <?php if($order['status']=='processing') echo 'selected'; ?>>В обработке</option>
                            <option value="shipped" <?php if($order['status']=='shipped') echo 'selected'; ?>>Отправлен</option>
                            <option value="cancelled" <?php if($order['status']=='cancelled') echo 'selected'; ?>>Отмена (Магазин)</option>
                            <option value="cancelled_by_user" <?php if($order['status']=='cancelled_by_user') echo 'selected'; ?>>Отмена (Клиент)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100 shadow-sm fw-bold">Обновить</button>
                    </div>
                </form>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <small class="text-muted fw-bold text-uppercase">Покупатель</small>
                        <div class="d-flex align-items-center mt-1">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span class="fw-bold"><?php echo htmlspecialchars($order['username']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6 mt-3 mt-md-0">
                        <small class="text-muted fw-bold text-uppercase">Контакты</small>
                        <p class="mb-0 lh-sm mt-1">
                            <?php echo htmlspecialchars($order['phone']); ?><br>
                            <span class="text-muted"><?php echo htmlspecialchars($order['address']); ?></span>
                        </p>
                    </div>
                </div>

                <hr class="text-muted opacity-25">

                <!-- СПИСОК ТОВАРОВ -->
                <h6 class="fw-bold mb-3">Состав заказа</h6>
                <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                    <?php foreach($items as $item): ?>
                    <a href="product.php?id=<?php echo $item['product_id']; ?>" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                        <img src="<?php echo $item['image'] ?: 'https://placehold.co/50'; ?>" width="50" height="50" style="object-fit: contain; mix-blend-mode: multiply;" class="me-3">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark">
                                <?php echo htmlspecialchars($item['title']); ?> 
                                <i class="bi bi-box-arrow-up-right small text-muted ms-1" style="font-size: 0.7em;"></i>
                            </div>
                            <small class="text-muted">Артикул: <?php echo $item['product_id']; ?></small>
                        </div>
                        <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                            <?php echo number_format($item['price_at_purchase'], 0, '', ' '); ?> ₽
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-end mt-4">
                    <span class="text-muted me-2">Итоговая сумма:</span>
                    <span class="fs-3 fw-bold text-primary"><?php echo number_format($order['total_price'], 0, '', ' '); ?> ₽</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ПРАВАЯ ЧАСТЬ: Чат с клиентом (Новый дизайн) -->
    <div class="col-lg-4">
        <div class="chat-container h-100" style="max-height: 700px;">
            <div class="chat-header">
                <i class="bi bi-chat-quote-fill text-warning me-2"></i> Чат с клиентом
            </div>
            
            <div class="chat-body" id="chatBox">
                <?php if(empty($messages)): ?>
                    <div class="text-center text-muted my-auto">
                        <i class="bi bi-chat-square-text display-4 opacity-25"></i>
                        <p class="mt-2 small">История переписки пуста</p>
                    </div>
                <?php else: ?>
                    <?php foreach($messages as $msg): 
                        // В админке: Админ - это "Я" (message-me), Юзер - это "Они" (message-them)
                        $isAdmin = ($msg['sender_role'] === 'admin'); 
                    ?>
                        <div class="message-bubble <?php echo $isAdmin ? 'message-me' : 'message-them'; ?>">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            <div class="msg-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-footer">
                <form method="post" class="chat-input-group">
                    <input type="text" name="reply_message" class="chat-input" placeholder="Ответ администратора..." required>
                    <button class="btn-send shadow-sm bg-warning text-dark border-0">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
    // Автопрокрутка
    var chatBox = document.getElementById("chatBox");
    if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
</script>
</div></body></html>