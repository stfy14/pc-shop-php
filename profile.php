<?php
require_once 'db.php';
require_once 'header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$stmt = $conn->prepare("SELECT role, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$_SESSION['role'] = $user['role'];
?>

<div class="row justify-content-center mt-4 mb-5">
    <div class="col-lg-10">
        
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-body p-4 p-md-5 d-flex align-items-center flex-wrap gap-4">
                <div class="bg-primary bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center shadow" 
                     style="width: 90px; height: 90px; font-size: 2.5rem; font-weight: bold;">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div>
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                    <span class="badge bg-light text-dark border rounded-pill px-3">
                        <?php echo $_SESSION['role'] === 'admin' ? 'Администратор' : 'Клиент'; ?>
                    </span>
                    
                    <div class="mt-3 d-flex gap-2">
                         <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin.php" class="btn btn-dark rounded-pill px-4">
                                <i class="bi bi-speedometer2"></i> Админка
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-outline-danger rounded-pill">Выйти</a>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="fw-bold mb-3 ps-2">📦 История заказов</h4>
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">Заказ</th>
                            <th>Дата</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th class="text-end pe-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
                        $stmt->execute([$_SESSION['user_id']]);
                        
                        if ($stmt->rowCount() > 0):
                            while($order = $stmt->fetch()):
                                $statusMap = [
                                    'new' => ['Новый', 'bg-primary'],
                                    'processing' => ['В работе', 'bg-warning text-dark'],
                                    'shipped' => ['Отправлен', 'bg-success'],
                                    'cancelled' => ['Отмена', 'bg-danger'],
                                    'cancelled_by_user' => ['Отменен вами', 'bg-secondary']
                                ];
                                $st = $statusMap[$order['status']] ?? [$order['status'], 'bg-secondary'];
                        ?>
                        <tr style="cursor: pointer;" onclick="window.location.href='order.php?uid=<?php echo $order['uuid']; ?>'">
                            <td class="ps-4">
                                <span class="fw-bold text-dark">#<?php echo $order['id']; ?></span><br>
                                <span class="text-muted small font-monospace"><?php echo substr($order['uuid'], 0, 8); ?></span>
                            </td>
                            <td class="text-muted"><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                            <td class="fw-bold"><?php echo number_format($order['total_price'], 0, '', ' '); ?> ₽</td>
                            <td><span class="badge rounded-pill <?php echo $st[1]; ?>"><?php echo $st[0]; ?></span></td>
                            <td class="text-end pe-4">
                                <a href="order.php?uid=<?php echo $order['uuid']; ?>" class="btn btn-light rounded-pill btn-sm px-3">
                                    Открыть
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam display-4 opacity-25"></i>
                                    <p class="mt-2">Заказов пока нет</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</div></body></html>