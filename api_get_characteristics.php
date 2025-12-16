<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_GET['cat_code'])) {
    echo json_encode([]);
    exit;
}

$cat_code = $_GET['cat_code'];

$stmt_cat = $conn->prepare("SELECT id FROM categories WHERE code = ?");
$stmt_cat->execute([$cat_code]);
$category = $stmt_cat->fetch();

if (!$category) {
    echo json_encode([]);
    exit;
}

$stmt_chars = $conn->prepare("SELECT id, name FROM characteristics WHERE category_id = ? ORDER BY name ASC");
$stmt_chars->execute([$category['id']]);
$characteristics = $stmt_chars->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($characteristics);