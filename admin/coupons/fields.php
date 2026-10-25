<?php
// the fields of the Add Coupon and Edit Coupon forms.
// create.php and edit.php set $errors (field => message) and $values (what to show in each field)
if (!isset($values, $errors)) {
    // opened directly in the browser instead of through create.php or edit.php
    header('Location: index.php');
    exit();
}
?>
<div class="row gx-3 mb-3">
    <div class="col-md-5">
        <label for="code" class="form-label">Coupon code</label>
        <input type="text" class="form-control text-uppercase font-monospace<?= invalid_class($errors, 'code') ?>" id="code" name="code"
            value="<?= e($values['code']) ?>" placeholder="SUMMER20">
        <?= field_error($errors, 'code') ?>
        <div class="form-text">Letters, numbers, dashes and underscores. Customers type this at checkout.</div>
    </div>
    <div class="col-md-7">
        <label for="description" class="form-label">Description (optional)</label>
        <input type="text" class="form-control<?= invalid_class($errors, 'description') ?>" id="description" name="description"
            value="<?= e($values['description']) ?>" placeholder="e.g. Summer sale: 20% off">
        <?= field_error($errors, 'description') ?>
    </div>
</div>

<div class="row gx-3 mb-3">
    <div class="col-md-4">
        <label for="discount_type" class="form-label">Kind of discount</label>
        <select class="form-select<?= invalid_class($errors, 'discount_type') ?>" id="discount_type" name="discount_type">
            <?php foreach (coupon_types() as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $values['discount_type'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'discount_type') ?>
    </div>
    <div class="col-md-4">
        <label for="discount_value" class="form-label">Discount</label>
        <input type="text" class="form-control<?= invalid_class($errors, 'discount_value') ?>" id="discount_value" name="discount_value"
            value="<?= e($values['discount_value']) ?>" placeholder="e.g. 10">
        <?= field_error($errors, 'discount_value') ?>
        <div class="form-text">Percent (1&ndash;100) or pesos, depending on the kind.</div>
    </div>
    <div class="col-md-4">
        <label for="min_order" class="form-label">Minimum order (optional)</label>
        <input type="text" class="form-control<?= invalid_class($errors, 'min_order') ?>" id="min_order" name="min_order"
            value="<?= e($values['min_order']) ?>" placeholder="e.g. 500">
        <?= field_error($errors, 'min_order') ?>
        <div class="form-text">Items total needed before the discount applies.</div>
    </div>
</div>

<div class="row gx-3 mb-3">
    <div class="col-md-4">
        <label for="starts_at" class="form-label">Valid from (optional)</label>
        <input type="date" class="form-control<?= invalid_class($errors, 'starts_at') ?>" id="starts_at" name="starts_at"
            value="<?= e($values['starts_at']) ?>">
        <?= field_error($errors, 'starts_at') ?>
    </div>
    <div class="col-md-4">
        <label for="expires_at" class="form-label">Valid until (optional)</label>
        <input type="date" class="form-control<?= invalid_class($errors, 'expires_at') ?>" id="expires_at" name="expires_at"
            value="<?= e($values['expires_at']) ?>">
        <?= field_error($errors, 'expires_at') ?>
        <div class="form-text">The coupon works through the end of this day.</div>
    </div>
    <div class="col-md-4">
        <label for="max_uses" class="form-label">Total uses allowed (optional)</label>
        <input type="text" class="form-control<?= invalid_class($errors, 'max_uses') ?>" id="max_uses" name="max_uses"
            value="<?= e($values['max_uses']) ?>" placeholder="no limit">
        <?= field_error($errors, 'max_uses') ?>
        <div class="form-text">Each customer can use a coupon once.</div>
    </div>
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select class="form-select<?= invalid_class($errors, 'status') ?>" id="status" name="status" style="max-width: 240px;">
        <option value="active" <?= $values['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $values['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <?= field_error($errors, 'status') ?>
</div>
