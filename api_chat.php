<?php
require_once 'db.php';
session_start();

// Ответ всегда в JSON
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['role'])) {
    echo json_encode(['error' => 'Auth required']);
    exit;
}

$action = $_POST['action'] ?? '';
$orderId = intval($_POST['order_id'] ?? 0);

// Проверка доступа к заказу (безопасность!)
// Нужно убедиться, что заказ принадлежит юзеру или это админ
$stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $order['user_id']) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// === ПОЛУЧЕНИЕ СООБЩЕНИЙ ===
if ($action === 'get_messages') {
    $stmt = $conn->prepare("SELECT * FROM order_messages WHERE order_id = ? ORDER BY created_at ASC");
    $stmt->execute([$orderId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Форматируем дату красиво
    foreach ($messages as &$msg) {
        $msg['time'] = date('H:i', strtotime($msg['created_at']));
    }
    
    echo json_encode(['messages' => $messages, 'current_role' => $_SESSION['role']]);
    exit;
}

// === ОТПРАВКА СООБЩЕНИЯ ===
if ($action === 'send_message') {
    $message = trim($_POST['message'] ?? '');
    if ($message) {
        $senderRole = ($_SESSION['role'] === 'admin') ? 'admin' : 'user';
        $stmt = $conn->prepare("INSERT INTO order_messages (order_id, sender_role, message) VALUES (?, ?, ?)");
        $stmt->execute([$orderId, $senderRole, $message]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['error' => 'Empty message']);
    }
    exit;
}
?>