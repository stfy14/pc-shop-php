<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) exit;

if (isset($_GET['id'])) {
    $orderId = intval($_GET['id']);
    $userId = $_SESSION['user_id'];

    // Проверяем владельца
    $stmt = $conn->prepare("SELECT uuid FROM orders WHERE id = ? AND user_id = ? AND status = 'new'");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if ($order) {
        // Ставим новый статус
        $update = $conn->prepare("UPDATE orders SET status = 'cancelled_by_user' WHERE id = ?");
        $update->execute([$orderId]);
        
        // Возвращаем в карточку заказа
        header("Location: order.php?uid=" . $order['uuid']);
        exit;
    }
}

header("Location: profile.php");
exit;
?>