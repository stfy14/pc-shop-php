<?php
require_once 'db.php';
require_once 'header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $qty   = intval($_POST['quantity']);
    $cat   = $_POST['category'];
    $desc  = $_POST['description'];
    
    $path = '';
    if (!empty($_FILES['img']['name'])) {
        $upload_dir = 'images/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $path = $upload_dir . time() . '_' . $_FILES['img']['name'];
        move_uploaded_file($_FILES['img']['tmp_name'], $path);
    }

    $stmt = $conn->prepare("INSERT INTO products (title, price, quantity, category, description, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $price, $qty, $cat, $desc, $path]);
    $product_id = $conn->lastInsertId();

    if ($product_id && isset($_POST['characteristics'])) {
        $char_stmt = $conn->prepare("INSERT INTO product_characteristics (product_id, characteristic_id, value) VALUES (?, ?, ?)");
        foreach ($_POST['characteristics'] as $char_id => $value) {
            if (!empty(trim($value))) {
                $char_stmt->execute([$product_id, intval($char_id), trim($value)]);
            }
        }
    }
    echo "<script>window.location.href='admin.php';</script>";
}
?>

<div class="row justify-content-center mt-4 mb-5">
    <div class="col-md-7">
        <div class="d-flex align-items-center mb-4">
            <a href="admin.php" class="btn btn-white border shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;"><i class="bi bi-arrow-left text-dark"></i></a>
            <h3 class="fw-bold mb-0">Новый товар</h3>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Название товара</label>
                        <input type="text" name="title" class="form-control form-control-lg bg-light border-0 rounded-3" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Цена (₽)</label>
                            <input type="number" name="price" class="form-control form-control-lg bg-light border-0 rounded-3" required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Остаток (шт)</label>
                            <input type="number" name="quantity" class="form-control form-control-lg bg-light border-0 rounded-3" value="10" required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Категория</label>
                            <select name="category" id="categorySelector" class="form-select form-select-lg bg-light border-0 rounded-3" required>
                                <option value="" disabled selected>-- Выберите --</option>
                                <?php
                                $cats_stmt = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                                foreach($cats_stmt->fetchAll() as $c): ?>
                                    <option value="<?php echo $c['code']; ?>"><?php echo $c['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Описание</label>
                        <textarea name="description" class="form-control form-control-lg bg-light border-0 rounded-3" rows="5"></textarea>
                    </div>

                    <div id="characteristics-container" class="mb-4"></div>

                    <div class="mb-5">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Изображение</label>
                        <div class="d-flex align-items-center p-3 rounded-3 bg-light">
                            <div class="bg-white p-2 rounded border me-3 flex-shrink-0" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <img src="" id="imagePreview" style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
                                <div id="imagePlaceholder"><i class="bi bi-image text-muted fs-3"></i></div>
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" name="img" class="form-control bg-white" id="imageUpload" accept="image/*">
                                <div class="text-muted small mt-1">Выберите файл для предпросмотра</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3">Создать товар</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('categorySelector').addEventListener('change', function() {
    const categoryCode = this.value;
    const container = document.getElementById('characteristics-container');
    container.innerHTML = '<div class="text-center text-muted p-3">Загрузка...</div>';

    if (!categoryCode) {
        container.innerHTML = '';
        return;
    }

    fetch(`api_get_characteristics.php?cat_code=${categoryCode}`)
        .then(response => response.json())
        .then(data => {
            container.innerHTML = '';
            if (data.length > 0) {
                 const title = document.createElement('h6');
                 title.className = 'fw-bold text-secondary small text-uppercase ls-1 mb-3';
                 title.innerText = 'Характеристики';
                 container.appendChild(title);
            }
            data.forEach(char => {
                const div = document.createElement('div');
                div.className = 'mb-3';
                const label = document.createElement('label');
                label.className = 'form-label text-muted small'; // ИЗМЕНЕНО
                label.innerText = char.name;
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `characteristics[${char.id}]`;
                // ИЗМЕНЕНО: Добавлены классы для соответствия дизайну
                input.className = 'form-control form-control-lg bg-light border-0 rounded-3'; 
                div.appendChild(label);
                div.appendChild(input);
                container.appendChild(div);
            });
        });
});
</script>

</div></body></html>