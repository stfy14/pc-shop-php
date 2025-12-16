<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getCartIds() {
    global $conn;

    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT product_id FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN); 
    }

    $cookieCart = $_COOKIE['cart_items'] ?? '[]';
    $ids = json_decode($cookieCart, true);
    return is_array($ids) ? $ids : [];
}

function addToCart($productId) {
    global $conn;
    $ids = getCartIds();

    if (in_array($productId, $ids)) return;

    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("INSERT IGNORE INTO cart (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $productId]);
    } else {
        $ids[] = $productId;
        setcookie('cart_items', json_encode(array_values($ids)), time() + (86400 * 30), "/");
    }
}

function removeFromCart($productId) {
    global $conn;
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $productId]);
    } else {
        $ids = getCartIds();
        $ids = array_diff($ids, [$productId]);
        setcookie('cart_items', json_encode(array_values($ids)), time() + (86400 * 30), "/");
    }
}

function clearCookieCart() {
    setcookie('cart_items', '', time() - 3600, "/");
}
?>