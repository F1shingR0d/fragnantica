<?php
session_start();
include('../../includes/config.php');
include('../../includes/functions.php');

// Coupon CRUD (create): admins only
require_admin();

list($errors, $old) = take_form('coupon_create');
$values = array_merge(array(
    'code' => '', 'description' => '', 'discount_type' => 'percent', 'discount_value' => '',
    'min_order' => '', 'max_uses' => '', 'starts_at' => '', 'expires_at' => '', 'status' => 'active',
), $old);

include('../../includes/header.php');
?>
<div class="container mt-4" style="max-width: 860px;">
    <a href="index.php" class="btn btn-link px-0">&larr; All coupons</a>
    <h2>Add Coupon</h2>
    <?php include('../../includes/alert.php'); ?>
    <?php if ($errors): ?>
        <div class="alert alert-warning">Please fix the <?= count($errors) ?> highlighted field<?= count($errors) === 1 ? '' : 's' ?> below.</div>
    <?php endif; ?>
    <!-- novalidate: the browser does not check the form, PHP does (store.php) -->
    <form action="store.php" method="POST" novalidate>
        <?= csrf_field() ?>
        <?php include('fields.php'); ?>
        <button type="submit" class="btn btn-primary" name="submit">Create coupon</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include('../../includes/footer.php'); ?>
