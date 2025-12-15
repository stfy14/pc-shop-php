<?php
require_once 'db.php';
require_once 'header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

if (!isset($_GET['id'])) exit;
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) die("Товар не найден");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $qty   = intval($_POST['quantity']);
    $cat   = $_POST['category'];
    $desc  = $_POST['description'];
    
    $image_path = $product['image'];

    if (!empty($_FILES['img']['name'])) {
        $path = 'images/' . time() . '_' . $_FILES['img']['name'];
        if (move_uploaded_file($_FILES['img']['tmp_name'], $path)) {
            $image_path = $path;
        }
    }

    $stmt = $conn->prepare("UPDATE products SET title=?, price=?, quantity=?, category=?, description=?, image=? WHERE id=?");
    $stmt->execute([$title, $price, $qty, $cat, $desc, $image_path, $id]);
    
    echo "<script>window.location.href='admin.php';</script>";
}
?>

<div class="row justify-content-center mt-4 mb-5">
    <div class="col-md-7">
        <div class="d-flex align-items-center mb-4">
            <a href="admin.php" class="btn btn-white border shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <i class="bi bi-arrow-left text-dark"></i>
            </a>
            <div>
                <h3 class="fw-bold mb-0">Редактирование</h3>
                <span class="text-muted small">ID: <?php echo $product['id']; ?></span>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <form action="" method="post" enctype="multipart/form-data">
                    
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Название товара</label>
                        <input type="text" name="title" class="form-control form-control-lg bg-light border-0 rounded-3" 
                               value="<?php echo htmlspecialchars($product['title']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Цена (₽)</label>
                            <input type="number" name="price" class="form-control form-control-lg bg-light border-0 rounded-3" 
                                   value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Остаток (шт)</label>
                            <input type="number" name="quantity" class="form-control form-control-lg bg-light border-0 rounded-3" 
                                   value="<?php echo $product['quantity']; ?>" required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Категория</label>
                            <select name="category" class="form-select form-select-lg bg-light border-0 rounded-3">
                                <?php
                                $cats = $conn->query("SELECT * FROM categories");
                                while($c = $cats->fetch()):
                                    $selected = ($product['category'] == $c['code']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $c['code']; ?>" <?php echo $selected; ?>>
                                        <?php echo $c['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Описание</label>
                        <textarea name="description" class="form-control bg-light border-0 rounded-3" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Изображение</label>
                        <div class="d-flex align-items-center p-3 border rounded-3 bg-light">
                            <?php if($product['image']): ?>
                                <div class="bg-white p-2 rounded border me-3 flex-shrink-0">
                                    <img src="<?php echo $product['image']; ?>" height="60" style="object-fit: contain;">
                                </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <input type="file" name="img" class="form-control form-control-sm mb-1">
                                <div class="text-muted small">Загрузите файл, чтобы заменить текущее фото</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3">
                            Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div></body></html>