<?php 
require_once 'db.php'; 
require_once 'header.php'; 
?>

<div class="mb-4 text-center">
    <?php $cat = $_GET['cat'] ?? null; ?>
    
    <a href="index.php" class="btn rounded-pill px-4 m-1 <?php echo !$cat ? 'btn-primary' : 'btn-light-primary'; ?>">
       Все
    </a>

    <?php
    $cats = $conn->query("SELECT * FROM categories");
    while($c = $cats->fetch()):
    ?>
    <a href="index.php?cat=<?php echo $c['code']; ?>" 
       class="btn rounded-pill px-4 m-1 <?php echo $cat == $c['code'] ? 'btn-primary' : 'btn-light-primary'; ?>">
       <?php echo $c['name']; ?>
    </a>
    <?php endwhile; ?>
</div>

<div class="row g-4">
    <?php
    $search = $_GET['q'] ?? null;

    if ($search) {
        $sql = "SELECT * FROM products WHERE (title LIKE ? OR description LIKE ?) AND is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $term = "%" . $search . "%";
        $stmt->execute([$term, $term]);
        echo "<div class='col-12'><h4 class='mb-3'>Результаты поиска: \"".htmlspecialchars($search)."\"</h4></div>";
    } elseif ($cat) {
        $sql = "SELECT * FROM products WHERE category = ? AND is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cat]);
    } else {
        $stmt = $conn->query("SELECT * FROM products WHERE is_deleted = 0");
    }

    if ($stmt->rowCount() > 0):
        while ($row = $stmt->fetch()): 
            $in_cart = in_array($row['id'], $ids_in_cart); 
            $is_out_of_stock = ($row['quantity'] <= 0);
    ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card-custom">
                <a href="product.php?id=<?php echo $row['id']; ?>" class="card-img-wrapper position-relative">
                    <?php if ($is_out_of_stock): ?>
                        <span class="position-absolute top-0 start-0 m-2 badge bg-secondary">Нет в наличии</span>
                    <?php endif; ?>
                    <img src="<?php echo $row['image'] ? $row['image'] : 'https://placehold.co/300x200?text=No+Image'; ?>" 
                         class="card-img-top" style="<?php echo $is_out_of_stock ? 'opacity:0.5' : ''; ?>">
                </a>
                
                <div class="card-body-custom">
                    <a href="product.php?id=<?php echo $row['id']; ?>" class="product-title">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </a>
                    <div class="product-cat"><?php echo htmlspecialchars($row['category']); ?></div>
                    
                    <div class="card-bottom">
                        <div class="price-tag"><?php echo number_format($row['price'], 0, '', ' '); ?> ₽</div>
                        
                        <?php if ($is_out_of_stock): ?>
                            <button class="btn btn-secondary btn-sm px-3 disabled" disabled>Нет</button>
                        <?php elseif ($in_cart): ?>
                            <a href="cart_action.php?action=remove&id=<?php echo $row['id']; ?>" 
                               class="btn btn-outline-danger btn-sm" title="Убрать из корзины">
                               <i class="bi bi-trash"></i>
                            </a>
                        <?php else: ?>
                            <a href="cart_action.php?action=add&id=<?php echo $row['id']; ?>" 
                               class="btn btn-primary btn-sm px-3">
                               Купить
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php 
        endwhile;
    else: 
        echo "<div class='col-12 text-center text-muted py-5'>Товары не найдены</div>";
    endif; 
    ?>
</div>

</div> 
</body>
</html>