<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Получить список ID товаров в корзине
 * Возвращает массив, например: [1, 5, 10]
 */
function getCartIds() {
    global $conn;

    // 1. Если пользователь авторизован -> берем из БД
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT product_id FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Возвращает плоский массив ID
    }

    // 2. Если гость -> берем из Cookie
    // Кука хранит JSON строку "[1, 5, 10]"
    $cookieCart = $_COOKIE['cart_items'] ?? '[]';
    $ids = json_decode($cookieCart, true);
    return is_array($ids) ? $ids : [];
}

/**
 * Добавить товар
 */
function addToCart($productId) {
    global $conn;
    $ids = getCartIds();

    // Если товар уже есть - выходим
    if (in_array($productId, $ids)) return;

    if (isset($_SESSION['user_id'])) {
        // БД: Пишем в таблицу
        $stmt = $conn->prepare("INSERT IGNORE INTO cart (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $productId]);
    } else {
        // COOKIE: Обновляем массив и сохраняем на 30 дней
        $ids[] = $productId;
        setcookie('cart_items', json_encode(array_values($ids)), time() + (86400 * 30), "/");
    }
}

/**
 * Удалить товар
 */
function removeFromCart($productId) {
    global $conn;
    
    if (isset($_SESSION['user_id'])) {
        // БД: Удаляем строку
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $productId]);
    } else {
        // COOKIE: Фильтруем массив и перезаписываем
        $ids = getCartIds();
        $ids = array_diff($ids, [$productId]);
        setcookie('cart_items', json_encode(array_values($ids)), time() + (86400 * 30), "/");
    }
}

/**
 * Очистить корзину (куки)
 * Используется после слияния при входе
 */
function clearCookieCart() {
    setcookie('cart_items', '', time() - 3600, "/");
}
?>