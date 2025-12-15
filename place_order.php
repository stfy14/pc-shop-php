<?php
require_once 'db.php';
require_once 'cart_core.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$address = $_POST['address'];
$phone = $_POST['phone'];

try {
    $conn->beginTransaction();

    $cartIds = getCartIds();
    if (empty($cartIds)) throw new Exception("Корзина пуста");

    $ids_string = implode(',', array_map('intval', $cartIds));
    $stmtItems = $conn->query("SELECT id, price FROM products WHERE id IN ($ids_string)");
    $products = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    $totalPrice = 0;
    foreach ($products as $p) {
        $totalPrice += $p['price'];
    }

    // Генерируем UUID
    $uuid = bin2hex(random_bytes(4)); 
    
    // Создаем заказ
    $stmtOrder = $conn->prepare("INSERT INTO orders (user_id, uuid, total_price, address, phone) VALUES (?, ?, ?, ?, ?)");
    $stmtOrder->execute([$userId, $uuid, $totalPrice, $address, $phone]);
    $orderId = $conn->lastInsertId();

    // Подготовка запросов
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, price_at_purchase) VALUES (?, ?, ?)");
    $stmtUpdateQty = $conn->prepare("UPDATE products SET quantity = quantity - 1 WHERE id = ? AND quantity > 0");

    foreach ($products as $p) {
        // Добавляем в order_items
        $stmtItem->execute([$orderId, $p['id'], $p['price']]);
        
        // ВАЖНО: Уменьшаем количество на складе!
        $stmtUpdateQty->execute([$p['id']]);
    }

    // Чистим корзину
    $conn->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
    setcookie('cart_items', '', time() - 3600, "/");

    $conn->commit();
    header("Location: profile.php?order_success=1");

} catch (Exception $e) {
    $conn->rollBack();
    die("Ошибка оформления заказа: " . $e->getMessage());
}
?>