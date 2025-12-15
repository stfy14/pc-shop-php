<?php
require_once 'db.php';
require_once 'header.php';

// Проверка админа
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $qty   = intval($_POST['quantity']); // Остаток
    $cat   = $_POST['category'];
    $desc  = $_POST['description'];
    
    $path = '';
    if (!empty($_FILES['img']['name'])) {
        $path = 'images/' . time() . '_' . $_FILES['img']['name'];
        move_uploaded_file($_FILES['img']['tmp_name'], $path);
    }

    $stmt = $conn->prepare("INSERT INTO products (title, price, quantity, category, description, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $price, $qty, $cat, $desc, $path]);
    echo "<script>window.location.href='admin.php';</script>";
}
?>

<div class="row justify-content-center mt-4 mb-5">
    <div class="col-md-7">
        <div class="d-flex align-items-center mb-4">
            <a href="admin.php" class="btn btn-white border shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <i class="bi bi-arrow-left text-dark"></i>
            </a>
            <h3 class="fw-bold mb-0">Новый товар</h3>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <form action="" method="post" enctype="multipart/form-data">
                    
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Название товара</label>
                        <input type="text" name="title" class="form-control form-control-lg bg-light border-0 rounded-3" placeholder="Например: RTX 4090..." required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Цена (₽)</label>
                            <input type="number" name="price" class="form-control form-control-lg bg-light border-0 rounded-3" placeholder="0" required>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Остаток (шт)</label>
                            <input type="number" name="quantity" class="form-control form-control-lg bg-light border-0 rounded-3" value="10" required>
                        </div>

                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Категория</label>
                            <select name="category" class="form-select form-select-lg bg-light border-0 rounded-3">
                                <?php
                                $cats = $conn->query("SELECT * FROM categories");
                                while($c = $cats->fetch()):
                                ?>
                                    <option value="<?php echo $c['code']; ?>"><?php echo $c['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Описание</label>
                        <textarea name="description" class="form-control bg-light border-0 rounded-3" rows="5" placeholder="Характеристики..."></textarea>
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Изображение</label>
                        <input type="file" name="img" class="form-control rounded-3">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3">
                            Создать товар
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div></body></html>