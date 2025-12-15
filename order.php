<?php
require_once 'db.php';
require_once 'header.php';

// ... (Тут проверки сессии и получение $order, $items, $messages без изменений) ...
// Скопируйте логику PHP из прошлого order.php
if (!isset($_SESSION['user_id']) || !isset($_GET['uid'])) exit;
$uuid = $_GET['uid'];
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE uuid = ? AND user_id = ?");
$stmt->execute([$uuid, $userId]);
$order = $stmt->fetch();
if (!$order) exit;

// Обработка сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        $conn->prepare("INSERT INTO order_messages (order_id, sender_role, message) VALUES (?, 'user', ?)")->execute([$order['id'], $msg]);
        echo "<script>window.location.href='order.php?uid=$uuid';</script>";
    }
}
// Товары
$stmtItems = $conn->prepare("SELECT p.id, p.title, p.image, oi.price_at_purchase FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmtItems->execute([$order['id']]);
$items = $stmtItems->fetchAll();
// Чат
$stmtChat = $conn->prepare("SELECT * FROM order_messages WHERE order_id = ? ORDER BY created_at ASC");
$stmtChat->execute([$order['id']]);
$messages = $stmtChat->fetchAll();

$statusMap = [
    'new' => ['Новый', 'bg-primary'],
    'processing' => ['В обработке', 'bg-warning text-dark'],
    'shipped' => ['Отправлен', 'bg-success'],
    'cancelled' => ['Отменен', 'bg-danger'],
    'cancelled_by_user' => ['Отменен вами', 'bg-secondary']
];
$st = $statusMap[$order['status']] ?? [$order['status'], 'bg-secondary'];
?>

<div class="row mt-4 mb-5">
    
    <!-- ИНФО О ЗАКАЗЕ -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom border-light py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Заказ #<?php echo substr($order['uuid'], 0, 8); ?></h5>
                <span class="badge rounded-pill <?php echo $st[1]; ?>"><?php echo $st[0]; ?></span>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <small class="text-muted fw-bold text-uppercase">Адрес доставки</small>
                        <p class="mb-0 fs-5"><?php echo htmlspecialchars($order['address']); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted fw-bold text-uppercase">Итого к оплате</small>
                        <p class="mb-0 fs-3 fw-bold text-primary"><?php echo number_format($order['total_price'], 0, '', ' '); ?> ₽</p>
                    </div>
                </div>

                <div class="list-group list-group-flush rounded-3 overflow-hidden border mb-3">
                    <?php foreach($items as $item): ?>
                    <a href="product.php?id=<?php echo $item['id']; ?>" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                        <img src="<?php echo $item['image'] ?: 'https://placehold.co/50'; ?>" width="50" height="50" style="object-fit: contain; mix-blend-mode: multiply;" class="me-3">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['title']); ?></div>
                            <small class="text-muted"><?php echo number_format($item['price_at_purchase'], 0, '', ' '); ?> ₽</small>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if($order['status'] === 'new'): ?>
                    <div class="text-end">
                        <a href="cancel_order.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-danger rounded-pill" onclick="return confirm('Отменить?')">
                            Отменить заказ
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ЧАТ ПОДДЕРЖКИ (НОВЫЙ ДИЗАЙН) -->
    <div class="col-lg-4">
        <div class="chat-container">
            <div class="chat-header">
                <i class="bi bi-headset text-primary me-2"></i> Поддержка
            </div>
            
            <div class="chat-body" id="chatBox">
                <?php if(empty($messages)): ?>
                    <div class="text-center text-muted my-auto">
                        <small>Напишите нам сообщение,<br>если есть вопросы.</small>
                    </div>
                <?php else: ?>
                    <?php foreach($messages as $msg): 
                        $isMe = ($msg['sender_role'] === 'user');
                    ?>
                        <div class="message-bubble <?php echo $isMe ? 'message-me' : 'message-them'; ?>">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            <div class="msg-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-footer">
                <form method="post" class="chat-input-group">
                    <textarea name="message" class="chat-input" rows="1" placeholder="Напишите сообщение..." required></textarea>
                    <button class="btn-send shadow-sm">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
<script>
    // Автопрокрутка чата вниз
    var chatBox = document.getElementById("chatBox");
    chatBox.scrollTop = chatBox.scrollHeight;
</script>
</div></body></html>