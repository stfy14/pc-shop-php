<?php
require_once 'cart_core.php'; 

$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'] ?? 'add';

    if ($action == 'add') {
        addToCart($id);
    } elseif ($action == 'remove') {
        removeFromCart($id);
    }
}

header("Location: $redirect");
exit;
?>