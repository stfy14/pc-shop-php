<?php
require_once 'db.php';
require_once 'header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">📦 Все заказы</h2>
    <a href="admin.php" class="btn btn-outline-secondary rounded-pill px-4">
        ← Назад к товарам
    </a>
</div>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Заказ</th>
                    <th>Клиент</th>
                    <th>Контакты</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th class="text-end pe-4"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT orders.*, users.username 
                        FROM orders 
                        JOIN users ON orders.user_id = users.id 
                        ORDER BY created_at DESC";
                $result = $conn->query($sql);

                while($order = $result->fetch()):
                    $statusMap = [
                        'new' => ['Новый', 'bg-primary'],
                        'processing' => ['В работе', 'bg-warning text-dark'],
                        'shipped' => ['Отправлен', 'bg-success'],
                        'cancelled' => ['Отмена (Маг.)', 'bg-danger'],
                        'cancelled_by_user' => ['Отмена (Клиент)', 'bg-secondary']
                    ];
                    $st = $statusMap[$order['status']] ?? [$order['status'], 'bg-secondary'];
                ?>
                <tr style="cursor: pointer;" onclick="window.location.href='admin_order_view.php?uid=<?php echo $order['uuid']; ?>'">
                    <td class="ps-4">
                        <span class="fw-bold">#<?php echo $order['id']; ?></span><br>
                        <small class="text-muted font-monospace"><?php echo substr($order['uuid'], 0); ?></small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                <i class="bi bi-person text-secondary"></i>
                            </div>
                            <?php echo htmlspecialchars($order['username']); ?>
                        </div>
                    </td>
                    <td>
                        <div class="small lh-sm">
                            <?php echo htmlspecialchars($order['phone']); ?><br>
                            <span class="d-inline-block text-truncate text-muted" style="max-width: 200px; vertical-align: bottom;">
                                <?php echo htmlspecialchars($order['address']); ?>
                            </span>
                        </div>
                    </td>
                    <td class="fw-bold text-nowrap">
                        <?php echo number_format($order['total_price'], 0, '', ' '); ?> ₽
                    </td>
                    <td>
                        <span class="badge rounded-pill <?php echo $st[1]; ?>">
                            <?php echo $st[0]; ?>
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <a href="admin_order_view.php?uid=<?php echo $order['uuid']; ?>" class="btn btn-sm btn-light border rounded-pill px-3">
                            Открыть <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</div></body></html>