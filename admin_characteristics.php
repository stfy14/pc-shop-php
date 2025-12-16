<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $cat_id = intval($_POST['category_id']);
    if ($name && $cat_id) {
        $stmt = $conn->prepare("INSERT INTO characteristics (category_id, name) VALUES (?, ?)");
        $stmt->execute([$cat_id, $name]);
        header("Location: admin_characteristics.php?cat_id=" . $cat_id); exit;
    }
}
if (isset($_GET['del']) && isset($_GET['cat_id'])) {
    $id_to_delete = intval($_GET['del']);
    $cat_id_redirect = intval($_GET['cat_id']);
    $conn->prepare("DELETE FROM characteristics WHERE id = ?")->execute([$id_to_delete]);
    $conn->prepare("DELETE FROM product_characteristics WHERE characteristic_id = ?")->execute([$id_to_delete]);
    header("Location: admin_characteristics.php?cat_id=" . $cat_id_redirect); exit;
}

require_once 'header.php';
$all_categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$current_category_id = $_GET['cat_id'] ?? $all_categories[0]['id'] ?? null;
?>

<div class="row justify-content-center mt-4">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">⚙️ Характеристики категорий</h2>
            <a href="admin.php" class="btn btn-outline-secondary rounded-pill">← Назад</a>
        </div>

        <!-- ИЗМЕНЕНО: Заменен стандартный select на кастомный -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="get" id="categoryFilterForm" class="d-flex align-items-center gap-3">
                    <label class="form-label mb-0 text-muted flex-shrink-0">Категория:</label>
                    <div class="custom-select-wrapper flex-grow-1">
                        <?php
                            $selected_cat_name = 'Категория не найдена';
                            foreach($all_categories as $cat) {
                                if ($cat['id'] == $current_category_id) {
                                    $selected_cat_name = $cat['name'];
                                    break;
                                }
                            }
                        ?>
                        <select name="cat_id" id="hiddenCategorySelect">
                            <?php foreach($all_categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $current_category_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="custom-select form-control form-control-lg bg-light border-0 rounded-3">
                            <span><?php echo htmlspecialchars($selected_cat_name); ?></span>
                        </div>
                        <div class="custom-select-options">
                            <?php foreach($all_categories as $cat): ?>
                                <div class="custom-select-option <?php echo ($cat['id'] == $current_category_id) ? 'is-selected' : ''; ?>" data-value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if($current_category_id): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Добавить характеристику</h5>
                <form method="post" action="admin_characteristics.php?cat_id=<?php echo $current_category_id; ?>" class="row g-3">
                    <input type="hidden" name="category_id" value="<?php echo $current_category_id; ?>">
                    <div class="col-md-9">
                        <!-- ИЗМЕНЕНО: Добавлены классы для соответствия дизайну -->
                        <input type="text" name="name" class="form-control form-control-lg bg-light border-0 rounded-3" placeholder="Название (напр. Сенсор, DPI...)" required>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button class="btn btn-primary btn-lg">Добавить</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th class="ps-4">ID</th><th>Название</th><th></th></tr></thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM characteristics WHERE category_id = ? ORDER BY name ASC");
                    $stmt->execute([$current_category_id]);
                    if ($stmt->rowCount() > 0):
                        while($row = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4">#<?php echo $row['id']; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="text-end pe-4"><a href="?del=<?php echo $row['id']; ?>&cat_id=<?php echo $current_category_id; ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Удалить?')"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">Характеристик нет.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-warning">Сначала <a href="admin_categories.php">создайте категорию</a>.</div>
        <?php endif; ?>
    </div>
</div>

<!-- ИЗМЕНЕНО: Добавлен JS для отправки формы при выборе кастомного селекта -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.getElementById('categoryFilterForm');
    const hiddenSelect = document.getElementById('hiddenCategorySelect');
    if (filterForm && hiddenSelect) {
        hiddenSelect.addEventListener('change', () => {
            filterForm.submit();
        });
    }
});
</script>
</div></body></html>