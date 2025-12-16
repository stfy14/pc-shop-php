<?php
require_once 'db.php';
require_once 'header.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['uid'])) echo "<script>window.location.href='index.php';</script>";
$uuid = $_GET['uid'];
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE uuid = ? AND user_id = ?");
$stmt->execute([$uuid, $userId]);
$order = $stmt->fetch();

if (!$order) { echo "<div class='container mt-5'>Заказ не найден.</div>"; exit; }

$stmtItems = $conn->prepare("SELECT p.id as product_id, p.title, p.image, oi.price_at_purchase FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmtItems->execute([$order['id']]);
$items = $stmtItems->fetchAll();

$statusMap = ['new' => ['Новый', 'bg-primary'], 'processing' => ['В обработке', 'bg-warning text-dark'], 'shipped' => ['Отправлен', 'bg-success'], 'cancelled' => ['Отменен', 'bg-danger'], 'cancelled_by_user' => ['Отменен вами', 'bg-secondary']];
$st = $statusMap[$order['status']] ?? [$order['status'], 'bg-secondary'];
?>

<style>
    body { overflow: hidden; }
    .full-height-container { height: calc(100vh - 80px); padding-bottom: 20px; }
    .scrollable-column { height: 100%; overflow-y: auto; padding-right: 10px; }
    .fixed-column { height: 100%; }
</style>

<div class="container full-height-container">
    <div class="row h-100">
        <div class="col-lg-8 scrollable-column">
            <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
                <div class="d-flex align-items-center">
                    <a href="profile.php" class="btn btn-white border shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="bi bi-arrow-left text-dark"></i>
                    </a>
                    <div>
                        <h4 class="fw-bold mb-0">Мой заказ</h4>
                        <span class="text-muted small font-monospace">UUID: <?php echo substr($order['uuid'], 0, 8); ?>...</span>
                    </div>
                </div>
                <span class="badge rounded-pill <?php echo $st[1]; ?> fs-6 px-3 py-2"><?php echo $st[0]; ?></span>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h5 class="mb-0 fw-bold">Детали заказа #<?php echo $order['id']; ?></h5>
                </div>
                <div class="card-body p-4 pt-0">
                    
                    <div class="row mb-4 mt-3">
                        <div class="col-md-6">
                            <small class="text-muted fw-bold text-uppercase">Получатель</small>
                            <div class="d-flex align-items-center mt-1">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
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

                    <h6 class="fw-bold mb-3">Состав заказа</h6>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach($items as $item): ?>
                        <a href="product.php?id=<?php echo $item['product_id']; ?>" class="d-flex align-items-center p-3 bg-light rounded-3 shadow-sm text-decoration-none order-item-link">
                            <img src="<?php echo $item['image'] ?: 'https://placehold.co/50'; ?>" width="50" height="50" style="object-fit: contain; mix-blend-mode: multiply;" class="me-3 bg-white rounded-2 p-1 border">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['title']); ?></div>
                                <small class="text-muted">Артикул: <?php echo $item['product_id']; ?></small>
                            </div>
                            <span class="badge bg-white text-dark rounded-pill px-3 py-2">
                                <?php echo number_format($item['price_at_purchase'], 0, '', ' '); ?> ₽
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <?php if($order['status'] === 'new'): ?>
                                <a href="cancel_order.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-outline-danger rounded-pill btn-sm"
                                   onclick="return confirm('Вы уверены, что хотите отменить заказ?')">
                                   Отменить заказ
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end">
                            <span class="text-muted me-2">Итоговая сумма:</span>
                            <span class="fs-3 fw-bold text-primary"><?php echo number_format($order['total_price'], 0, '', ' '); ?> ₽</span>
                        </div>
                    </div>
                </div>
            </div>
            <div style="height: 20px;"></div>
        </div>

        <div class="col-lg-4 fixed-column pt-2">
            <div class="chat-container">
                <div class="chat-header">
                    <i class="bi bi-headset text-primary me-2"></i> Поддержка
                </div>
                
                <div class="chat-body" id="chatBox">
                    <div class="text-center text-muted my-auto loading-text">Загрузка...</div>
                </div>

                <div class="chat-footer">
                    <form id="chatForm" class="chat-input-group">
                        <div class="chat-input-wrapper">
                            <textarea class="chat-input" id="msgInput" placeholder="Введите сообщение..." rows="1"></textarea>
                        </div>
                        <button type="submit" class="btn-send shadow-sm">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const orderId = <?php echo $order['id']; ?>;
    const chatBox = document.getElementById("chatBox");
    const chatForm = document.getElementById("chatForm");
    const msgInput = document.getElementById("msgInput");
    const msgInputWrapper = msgInput.parentElement;
    
    const ghost = document.createElement('textarea');
    ghost.rows = 1;
    ghost.style.position = 'absolute';
    ghost.style.top = '-9999px';
    ghost.style.left = '0';
    ghost.style.visibility = 'hidden';
    document.body.appendChild(ghost);
    
    let ONE_LINE_HEIGHT, MAX_HEIGHT;
    const MAX_LINES = 6;
    let scrollbarTimeout;

    function calculateHeights() {
        const computedStyle = getComputedStyle(msgInput);
        [
            'width', 'font', 'lineHeight', 'letterSpacing', 
            'paddingTop', 'paddingBottom', 'paddingLeft', 'paddingRight',
            'borderWidth', 'boxSizing'
        ].forEach(key => {
            ghost.style[key] = computedStyle[key];
        });

        ghost.value = 'a';
        ONE_LINE_HEIGHT = ghost.scrollHeight;
        ghost.value = '';
        
        const lineHeight = parseFloat(computedStyle.lineHeight);
        const paddingTop = parseFloat(computedStyle.paddingTop);
        const paddingBottom = parseFloat(computedStyle.paddingBottom);
        
        MAX_HEIGHT = (lineHeight * MAX_LINES) + paddingTop + paddingBottom;
        
        msgInputWrapper.style.gridTemplateRows = ONE_LINE_HEIGHT + 'px';
    }
    calculateHeights();

    function autoResize() {
        clearTimeout(scrollbarTimeout);
        msgInput.style.overflowY = 'hidden';

        ghost.value = msgInput.value;
        let scrollHeight = ghost.scrollHeight;
        let newHeight;
        
        if (msgInput.value === '') {
            newHeight = ONE_LINE_HEIGHT;
            chatForm.classList.remove('is-expanded');
        } else if (scrollHeight >= MAX_HEIGHT) {
            newHeight = MAX_HEIGHT;
            chatForm.classList.add('is-expanded');
            
            scrollbarTimeout = setTimeout(() => {
                msgInput.style.overflowY = 'auto';
            }, 200);
        } else {
            newHeight = scrollHeight;
            if (newHeight > ONE_LINE_HEIGHT + 10) {
                 chatForm.classList.add('is-expanded');
            } else {
                 chatForm.classList.remove('is-expanded');
            }
        }
        
        msgInputWrapper.style.gridTemplateRows = newHeight + 'px';
    }

    msgInput.addEventListener('input', autoResize);

    msgInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        let text = msgInput.value.trim();
        if (!text) return;
        
        let formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('order_id', orderId);
        formData.append('message', text);
        
        msgInput.value = '';
        autoResize(); 
        msgInput.focus();

        fetch('api_chat.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { loadMessages(); });
    });

    function loadMessages() {
        let formData = new FormData();
        formData.append('action', 'get_messages');
        formData.append('order_id', orderId);

        fetch('api_chat.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.error) return;
            let html = '';
            if (data.messages.length === 0) {
                html = '<div class="text-center text-muted my-auto"><small>История пуста.</small></div>';
            } else {
                data.messages.forEach(msg => {
                    let isMe = (msg.sender_role === 'user'); 
                    let bubbleClass = isMe ? 'message-me' : 'message-them';
                    html += `<div class="message-bubble ${bubbleClass}">${msg.message.replace(/\n/g, '<br>')}<div class="msg-time">${msg.time}</div></div>`;
                });
            }
            if (chatBox.innerHTML.replace(/\s/g,'') !== html.replace(/\s/g,'')) {
                 chatBox.innerHTML = html;
                 chatBox.scrollTop = chatBox.scrollHeight;
            }
        });
    }

    loadMessages();
    setInterval(loadMessages, 2000);
});
</script>
</div></body></html>```