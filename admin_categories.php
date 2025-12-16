<?php
require_once 'db.php';
require_once 'header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    if($name && $code) {
        $stmt = $conn->prepare("INSERT INTO categories (name, code) VALUES (?, ?)");
        $stmt->execute([$name, $code]);
    }
}

if (isset($_GET['del'])) {
    $conn->prepare("DELETE FROM categories WHERE id = ?")->execute([$_GET['del']]);
    echo "<script>window.location.href='admin_categories.php';</script>";
}
?>

<div class="row justify-content-center mt-4">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">📂 Категории</h2>
            <a href="admin.php" class="btn btn-outline-secondary rounded-pill">← Назад</a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <form method="post" class="row g-3">
                    <div class="col-md-5">
                        <!-- ИЗМЕНЕНО: Добавлены классы для соответствия дизайну -->
                        <input type="text" name="name" class="form-control form-control-lg bg-light border-0 rounded-3" placeholder="Название (напр. Наушники)" required>
                    </div>
                    <div class="col-md-5">
                        <!-- ИЗМЕНЕНО: Добавлены классы для соответствия дизайну -->
                        <input type="text" name="code" class="form-control form-control-lg bg-light border-0 rounded-3" placeholder="Код (lat) (напр. headphones)" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary btn-lg">Добавить</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr> <th class="ps-4">ID</th> <th>Название</th> <th>Код (URL)</th> <th></th> </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                    while($row = $stmt->fetch()): ?>
                    <tr>
                        <td class="ps-4">#<?php echo $row['id']; ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="font-monospace text-muted"><?php echo htmlspecialchars($row['code']); ?></td>
                        <td class="text-end pe-4"><a href="?del=<?php echo $row['id']; ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Удалить?')"><i class="bi bi-trash"></i></a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div></body></html>