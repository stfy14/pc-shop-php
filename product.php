<?php
require_once 'db.php';
require_once 'header.php';

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='container mt-5 py-5 text-center'><h3>Товар не найден (404)</h3><a href='index.php' class='btn btn-primary mt-3'>Вернуться в каталог</a></div>";
    exit;
}

$category_name = htmlspecialchars($product['category']);
$stmt_cat = $conn->prepare("SELECT name FROM categories WHERE code = ?");
$stmt_cat->execute([$product['category']]);
$category_result = $stmt_cat->fetch();
if ($category_result) {
    $category_name = htmlspecialchars($category_result['name']);
}

$char_stmt = $conn->prepare(
    "SELECT c.name, pc.value 
     FROM product_characteristics pc
     JOIN characteristics c ON pc.characteristic_id = c.id
     WHERE pc.product_id = ? ORDER BY c.name ASC"
);
$char_stmt->execute([$id]);
$product_characteristics = $char_stmt->fetchAll();

$is_archived = ($product['is_deleted'] == 1);
$is_out_of_stock = ($product['quantity'] <= 0);
$can_buy = (!$is_archived && !$is_out_of_stock);
$in_cart = in_array($product['id'], $ids_in_cart);
?>

<div class="row mt-4">
    <?php if ($is_archived): ?>
        <div class="col-12 mb-4">
            <div class="alert alert-danger rounded-4 d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-0">Товар снят с продажи</h5>
                    <p class="mb-0 small">Эта страница доступна только для просмотра (архив).</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-md-6 mb-4">
        <div class="bg-white p-5 rounded-4 shadow-sm d-flex justify-content-center align-items-center" style="min-height: 400px;">
            <img src="<?php echo $product['image'] ? $product['image'] : 'https://placehold.co/600x400'; ?>" class="img-fluid" style="max-height: 350px;">
        </div>
    </div>

    <div class="col-md-6">
        <a href="index.php?cat=<?php echo htmlspecialchars($product['category']); ?>" class="product-cat text-decoration-none d-inline-block mb-2"><?php echo $category_name; ?></a>
        <h1 class="fw-bold mb-2 text-dark"><?php echo htmlspecialchars($product['title']); ?></h1>
        <div class="mb-3">
            <?php if ($is_archived): ?> <span class="badge bg-danger rounded-pill">Снят с продажи</span>
            <?php elseif ($is_out_of_stock): ?> <span class="badge bg-secondary rounded-pill">Закончился</span>
            <?php else: ?> <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill"><i class="bi bi-check-circle-fill"></i> В наличии: <?php echo $product['quantity']; ?> шт.</span>
            <?php endif; ?>
        </div>
        <div class="mb-4"><span class="display-5 fw-bold text-primary"><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</span></div>
        <div class="d-grid gap-2 d-md-block mb-4">
            <?php if ($can_buy): ?>
                <?php if ($in_cart): ?>
                    <button class="btn btn-success btn-lg px-5 disabled">✓ Товар уже в корзине</button>
                    <button class="btn btn-outline-dark btn-lg" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart">Оформить</button>
                <?php else: ?>
                    <a href="cart_action.php?action=add&id=<?php echo $product['id']; ?>" class="btn btn-primary btn-lg px-5 shadow-sm">Добавить в корзину</a>
                <?php endif; ?>
            <?php else: ?>
                <button class="btn btn-secondary btn-lg px-5 disabled" disabled><?php echo $is_archived ? 'Недоступно' : 'Нет в наличии'; ?></button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty(trim($product['description'])) || !empty($product_characteristics)): ?>
<div class="row">
    <div class="col-12">
        <div class="card card-body bg-white border-0 shadow-sm rounded-4 p-4 p-lg-5 mb-5">
            <?php if (!empty($product_characteristics)): ?>
                <h5 class="mb-3 fw-bold">Основные характеристики</h5>
                <table class="table table-sm table-borderless mb-4" style="max-width: 500px;">
                    <tbody>
                        <?php foreach($product_characteristics as $char): ?>
                        <tr>
                            <td class="text-muted" style="width: 40%;"><?php echo htmlspecialchars($char['name']); ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($char['value']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <?php if (!empty(trim($product['description']))): ?>
                <h5 class="mb-3 fw-bold">Описание товара</h5>
                <p class="mb-0 text-dark" style="white-space: pre-line; line-height: 1.6;"><?php echo htmlspecialchars($product['description']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

</div></body></html>