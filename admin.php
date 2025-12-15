<?php
require_once 'db.php';
// Проверка прав
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// === ЛОГИКА ДЕЙСТВИЙ ===

// 1. Удаление (в архив)
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $conn->prepare("UPDATE products SET is_deleted = 1 WHERE id = ?")->execute([$id]);
    header("Location: admin.php"); // Возврат на активные
    exit;
}

// 2. Восстановление (из архива)
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    $conn->prepare("UPDATE products SET is_deleted = 0 WHERE id = ?")->execute([$id]);
    header("Location: admin.php?tab=deleted"); // Возврат в архив
    exit;
}

require_once 'header.php';

// Какую вкладку показываем?
$tab = $_GET['tab'] ?? 'active'; // 'active' или 'deleted'
$is_deleted = ($tab === 'deleted') ? 1 : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">🛠️ Панель управления</h2>
    </div>
    <div class="d-flex gap-2">
        <a href="admin_orders.php" class="btn btn-dark px-4 rounded-pill">
            <i class="bi bi-box-seam"></i> Заказы
        </a>
        <a href="admin_categories.php" class="btn btn-outline-dark px-4 rounded-pill">
            <i class="bi bi-tags"></i> Категории
        </a>
        <a href="add.php" class="btn btn-primary px-4 rounded-pill">
            <i class="bi bi-plus-lg"></i> Добавить товар
        </a>
    </div>
</div>

<!-- ВКЛАДКИ -->
<ul class="nav nav-pills mb-3">
  <li class="nav-item">
    <a class="nav-link rounded-pill px-4 <?php echo $tab === 'active' ? 'active' : 'bg-white text-dark border'; ?>" href="admin.php">
        Активные товары
    </a>
  </li>
  <li class="nav-item ms-2">
    <a class="nav-link rounded-pill px-4 <?php echo $tab === 'deleted' ? 'active bg-danger' : 'bg-white text-danger border border-danger'; ?>" href="admin.php?tab=deleted">
        <i class="bi bi-trash"></i> Корзина / Архив
    </a>
  </li>
</ul>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Фото</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Остаток</th> <!-- НОВОЕ -->
                    <th>Категория</th>
                    <th class="text-end pe-4">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Фильтруем по статусу is_deleted
                $stmt = $conn->prepare("SELECT * FROM products WHERE is_deleted = ? ORDER BY id DESC");
                $stmt->execute([$is_deleted]);
                
                if ($stmt->rowCount() > 0):
                    while ($row = $stmt->fetch()): 
                        // Подсветка, если товара мало
                        $stockBadge = 'bg-success';
                        if ($row['quantity'] == 0) $stockBadge = 'bg-danger';
                        elseif ($row['quantity'] < 5) $stockBadge = 'bg-warning text-dark';
                ?>
                <tr>
                    <td class="ps-4 text-muted">#<?php echo $row['id']; ?></td>
                    <td>
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <img src="<?php echo $row['image'] ?: 'https://placehold.co/50'; ?>" 
                                 style="max-width: 100%; max-height: 100%; mix-blend-mode: multiply;">
                        </div>
                    </td>
                    <td>
                        <a href="product.php?id=<?php echo $row['id']; ?>" class="fw-bold text-decoration-none text-dark" target="_blank">
                            <?php echo htmlspecialchars($row['title']); ?> 
                            <i class="bi bi-box-arrow-up-right small text-muted ms-1"></i>
                        </a>
                    </td>
                    <td><?php echo number_format($row['price'], 0, '', ' '); ?> ₽</td>
                    
                    <!-- КОЛИЧЕСТВО -->
                    <td>
                        <span class="badge rounded-pill <?php echo $stockBadge; ?>">
                            <?php echo $row['quantity']; ?> шт.
                        </span>
                    </td>
                    
                    <td><span class="badge bg-secondary rounded-pill"><?php echo $row['category']; ?></span></td>
                    
                    <td class="text-end pe-4">
                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Редактировать">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        
                        <?php if ($tab === 'active'): ?>
                            <!-- Кнопка В АРХИВ -->
                            <a href="admin.php?del=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('В архив?');" title="Удалить">
                               <i class="bi bi-trash"></i>
                            </a>
                        <?php else: ?>
                            <!-- Кнопка ВОССТАНОВИТЬ -->
                            <a href="admin.php?restore=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-success" 
                               title="Восстановить">
                               <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; 
                else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Список пуст</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div></body></html>