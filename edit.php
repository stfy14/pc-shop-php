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
        $upload_dir = 'images/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $path = $upload_dir . time() . '_' . $_FILES['img']['name'];
        if (move_uploaded_file($_FILES['img']['tmp_name'], $path)) {
            $image_path = $path;
        }
    }

    $stmt = $conn->prepare("UPDATE products SET title=?, price=?, quantity=?, category=?, description=?, image=? WHERE id=?");
    $stmt->execute([$title, $price, $qty, $cat, $desc, $image_path, $id]);
    
    $conn->prepare("DELETE FROM product_characteristics WHERE product_id = ?")->execute([$id]);

    if (isset($_POST['characteristics'])) {
        $char_stmt = $conn->prepare("INSERT INTO product_characteristics (product_id, characteristic_id, value) VALUES (?, ?, ?)");
        foreach ($_POST['characteristics'] as $char_id => $value) {
            if (!empty(trim($value))) {
                $char_stmt->execute([$id, intval($char_id), trim($value)]);
            }
        }
    }
    
    echo "<script>window.location.href='admin.php';</script>";
}

$char_values_stmt = $conn->prepare("SELECT characteristic_id, value FROM product_characteristics WHERE product_id = ?");
$char_values_stmt->execute([$id]);
$product_char_values = $char_values_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="row justify-content-center mt-4 mb-5">
    <div class="col-md-7">
        <div class="d-flex align-items-center mb-4">
            <a href="admin.php" class="btn btn-white border shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;"><i class="bi bi-arrow-left text-dark"></i></a>
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
                        <input type="text" name="title" class="form-control form-control-lg bg-light border-0 rounded-3" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Цена (₽)</label>
                            <input type="number" name="price" class="form-control form-control-lg bg-light border-0 rounded-3" value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Остаток (шт)</label>
                            <input type="number" name="quantity" class="form-control form-control-lg bg-light border-0 rounded-3" value="<?php echo $product['quantity']; ?>" required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Категория</label>
                            <select name="category" id="categorySelector" class="form-select form-select-lg bg-light border-0 rounded-3" required>
                                <?php
                                $cats_stmt = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                                foreach($cats_stmt->fetchAll() as $c): ?>
                                    <option value="<?php echo $c['code']; ?>" <?php echo ($product['category'] == $c['code']) ? 'selected' : ''; ?>>
                                        <?php echo $c['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Описание</label>
                        <textarea name="description" class="form-control form-control-lg bg-light border-0 rounded-3" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    
                    <div id="characteristics-container" class="mb-4"></div>

                    <div class="mb-5">
                        <label class="form-label text-secondary small fw-bold text-uppercase ls-1">Изображение</label>
                        <div class="d-flex align-items-center p-3 rounded-3 bg-light">
                            <div class="bg-white p-2 rounded border me-3 flex-shrink-0" style="width: 80px; height: 80px; display: flex; align-items-center; justify-content-center;">
                                <img src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>" id="imagePreview" style="max-width: 100%; max-height: 100%; object-fit: contain; <?php echo empty($product['image']) ? 'display: none;' : ''; ?>">
                                <div id="imagePlaceholder" style="<?php echo !empty($product['image']) ? 'display: none;' : ''; ?>"><i class="bi bi-image text-muted fs-3"></i></div>
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" name="img" class="form-control bg-white" id="imageUpload" accept="image/*">
                                <div class="text-muted small mt-1">Загрузите новый файл, чтобы заменить фото</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const existingValues = <?php echo json_encode($product_char_values); ?>;
const categorySelector = document.getElementById('categorySelector');
const container = document.getElementById('characteristics-container');

function loadCharacteristics(categoryCode) {
    container.innerHTML = '<div class="text-center text-muted p-3">Загрузка...</div>';
    if (!categoryCode) { container.innerHTML = ''; return; }

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
                if (existingValues[char.id]) {
                    input.value = existingValues[char.id];
                }
                div.appendChild(label);
                div.appendChild(input);
                container.appendChild(div);
            });
        });
}

document.addEventListener('DOMContentLoaded', function() {
    loadCharacteristics(categorySelector.value);
});

categorySelector.addEventListener('change', function() {
    for (const key in existingValues) { delete existingValues[key]; }
    loadCharacteristics(this.value);
});
</script>

</div></body></html>