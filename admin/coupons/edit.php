<?php
session_start();
include('../../includes/config.php');
include('../../includes/functions.php');

// Coupon CRUD (update): admins only
require_admin();

$coupon_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$coupon = $coupon_id ? mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT * FROM coupon WHERE coupon_id = ? LIMIT 1", [$coupon_id])) : null;
if (!$coupon) {
    $_SESSION['message'] = 'Coupon not found.';
    header("Location: index.php");
    exit();
}
$timesUsed = coupon_times_used($conn, $coupon_id);

// after a failed update show what the admin typed, otherwise show the saved values
list($errors, $old) = take_form('coupon_edit_' . $coupon_id);
$values = array_merge(array(
    'code' => $coupon['code'],
    'description' => (string)$coupon['description'],
    'discount_type' => $coupon['discount_type'],
    'discount_value' => $coupon['discount_value'],
    'min_order' => (float)$coupon['min_order'] > 0 ? $coupon['min_order'] : '',
    'max_uses' => (string)$coupon['max_uses'],
    'starts_at' => (string)$coupon['starts_at'],
    'expires_at' => (string)$coupon['expires_at'],
    'status' => $coupon['status'],
), $old);

include('../../includes/header.php');
?>
<div class="container mt-4" style="max-width: 860px;">
    <a href="index.php" class="btn btn-link px-0">&larr; All coupons</a>
    <h2>Edit Coupon <span class="font-monospace"><?= e($coupon['code']) ?></span></h2>
    <?php include('../../includes/alert.php'); ?>
    <?php if ($timesUsed > 0): ?>
        <div class="alert alert-info small">
            This coupon was used on <?= $timesUsed ?> order<?= $timesUsed === 1 ? '' : 's' ?>.
            Changes only affect new orders; past orders keep the discount they got.
        </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-warning">Please fix the <?= count($errors) ?> highlighted field<?= count($errors) === 1 ? '' : 's' ?> below.</div>
    <?php endif; ?>
    <!-- novalidate: the browser does not check the form, PHP does (update.php) -->
    <form action="update.php" method="POST" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="coupon_id" value="<?= (int)$coupon_id ?>">
        <?php include('fields.php'); ?>
        <button type="submit" class="btn btn-primary" name="submit">Save changes</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include('../../includes/footer.php'); ?>
