<?php
session_start();
include('../../includes/config.php');
include('../../includes/functions.php');

// Coupon CRUD: admins only
require_admin();

// every coupon with how often it was used and how much it took off (cancelled orders do not count)
$sql = "SELECT c.*,
               COUNT(o.order_id) AS times_used,
               COALESCE(SUM(o.discount_amount), 0) AS total_discount,
               (SELECT COUNT(*) FROM orders o2 WHERE o2.coupon_id = c.coupon_id) AS all_orders
        FROM coupon c
        LEFT JOIN orders o ON o.coupon_id = c.coupon_id AND o.status <> 'cancelled'
        GROUP BY c.coupon_id
        ORDER BY c.created_at DESC, c.coupon_id DESC";
$coupons = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);

include('../../includes/header.php');
?>
<div class="container mt-4">
    <?php include('../../includes/alert.php'); ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="mb-0">Coupons</h2>
        <a href="create.php" class="btn btn-primary"><i class="fa-solid fa-ticket me-1"></i> Add Coupon</a>
    </div>
    <p class="text-muted small mt-2">Customers enter a coupon code at checkout to get a discount on their order.</p>

    <?php if (empty($coupons)): ?>
        <p class="mt-4">No coupons yet. <a href="create.php">Add the first one</a>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Code</th><th>Discount</th><th>Minimum order</th><th>Valid</th><th>Used</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($coupons as $c):
                    $id = (int)$c['coupon_id'];
                    $used = (int)$c['times_used'];
                    $active = $c['status'] === 'active';
                ?>
                    <tr class="<?= $active ? '' : 'table-secondary' ?>">
                        <td>
                            <strong class="font-monospace"><?= e($c['code']) ?></strong>
                            <?php if ($c['description'] !== null && $c['description'] !== ''): ?>
                                <br><small class="text-muted"><?= e($c['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= coupon_label($c) ?></td>
                        <td><?= (float)$c['min_order'] > 0 ? peso($c['min_order']) : '<span class="text-muted">none</span>' ?></td>
                        <td class="small">
                            <?= $c['starts_at'] ? date('M j, Y', strtotime($c['starts_at'])) : 'Any time' ?>
                            &ndash;
                            <?= $c['expires_at'] ? date('M j, Y', strtotime($c['expires_at'])) : 'no end' ?>
                        </td>
                        <td>
                            <?= $used ?><?= $c['max_uses'] !== null ? ' / ' . (int)$c['max_uses'] : '' ?>
                            <?php if ($used > 0): ?><br><small class="text-muted"><?= peso($c['total_discount']) ?> off</small><?php endif; ?>
                        </td>
                        <td><?= coupon_state_badge($c, $used) ?></td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
                                <form action="status.php" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="coupon_id" value="<?= $id ?>">
                                    <?php if ($active): ?>
                                        <button type="submit" name="status" value="inactive" class="btn btn-sm btn-outline-warning">
                                            <i class="fa-solid fa-ban"></i> Deactivate</button>
                                    <?php else: ?>
                                        <button type="submit" name="status" value="active" class="btn btn-sm btn-outline-success">
                                            <i class="fa-solid fa-check"></i> Activate</button>
                                    <?php endif; ?>
                                </form>
                                <?php if ((int)$c['all_orders'] === 0): ?>
                                    <form action="delete.php" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="coupon_id" value="<?= $id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                                            onclick="return confirm('Delete coupon <?= e($c['code']) ?>? This cannot be undone.');">
                                            <i class="fa-solid fa-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="small text-muted">Coupons that were used on an order cannot be deleted, so the order keeps its discount record. Deactivate them instead.</p>
    <?php endif; ?>
</div>
<?php include('../../includes/footer.php'); ?>
