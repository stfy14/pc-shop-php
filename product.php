<?php
require_once 'db.php';
require_once 'header.php';

$id = intval($_GET['id']);
// Ищем даже удаленный товар
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='container mt-5 py-5 text-center'><h3>Товар не найден (404)</h3><a href='index.php' class='btn btn-primary mt-3'>Вернуться в каталог</a></div>";
    exit;
}

// СТАТУСЫ
$is_archived = ($product['is_deleted'] == 1);
$is_out_of_stock = ($product['quantity'] <= 0);
$can_buy = (!$is_archived && !$is_out_of_stock);
$in_cart = in_array($product['id'], $ids_in_cart);
?>

<div class="row mt-4 mb-5">
    
    <!-- ПЛАШКА: ТОВАР В АРХИВЕ (Оставляем сверху как предупреждение) -->
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

    <!-- Левая колонка: Картинка -->
    <div class="col-md-6 mb-4">
        <div class="bg-white p-5 rounded-4 shadow-sm border d-flex justify-content-center align-items-center" style="min-height: 400px;">
            
            <!-- Здесь больше нет никаких наложений (плашек), только чистое фото -->
            <img src="<?php echo $product['image'] ? $product['image'] : 'https://placehold.co/600x400'; ?>" 
                 class="img-fluid" style="max-height: 350px;">
        </div>
    </div>

    <!-- Правая колонка: Инфо -->
    <div class="col-md-6">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Главная</a></li>
                <li class="breadcrumb-item text-muted"><?php echo htmlspecialchars($product['category']); ?></li>
            </ol>
        </nav>

        <h1 class="fw-bold mb-2 text-dark">
            <?php echo htmlspecialchars($product['title']); ?>
        </h1>
        
        <!-- СТАТУСЫ (Бейджи) -->
        <div class="mb-3">
            <?php if ($is_archived): ?>
                <span class="badge bg-danger rounded-pill">Снят с продажи</span>
            <?php elseif ($is_out_of_stock): ?>
                <span class="badge bg-secondary rounded-pill">Закончился</span>
            <?php else: ?>
                <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill">
                    <i class="bi bi-check-circle-fill"></i> В наличии: <?php echo $product['quantity']; ?> шт.
                </span>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <span class="display-5 fw-bold text-primary"><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</span>
        </div>

        <!-- КНОПКИ -->
        <div class="d-grid gap-2 d-md-block mb-4">
            <?php if ($can_buy): ?>
                <?php if ($in_cart): ?>
                    <button class="btn btn-success btn-lg px-5 disabled">✓ Товар уже в корзине</button>
                    <button class="btn btn-outline-dark btn-lg" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart">
                        Оформить
                    </button>
                <?php else: ?>
                    <a href="cart_action.php?add=<?php echo $product['id']; ?>" class="btn btn-primary btn-lg px-5 shadow-sm">
                        Добавить в корзину
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <!-- Кнопка заблокирована, текст объясняет причину -->
                <button class="btn btn-secondary btn-lg px-5 disabled" disabled>
                    <?php echo $is_archived ? 'Недоступно' : 'Нет в наличии'; ?>
                </button>
            <?php endif; ?>
        </div>

        <div class="card card-body bg-white border-0 shadow-sm rounded-4 p-4">
            <h5 class="mb-3 fw-bold">Описание товара</h5>
            <p class="mb-0 text-secondary" style="white-space: pre-line; line-height: 1.6;">
                <?php echo htmlspecialchars($product['description']); ?>
            </p>
        </div>
    </div>
</div>
</div></body></html>