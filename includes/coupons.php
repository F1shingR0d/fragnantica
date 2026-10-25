<?php
// discount coupons: the admin's coupon form, checking a code at checkout and working out the discount
// loaded by includes/functions.php

function coupon_types()
{
    return array(
        'percent' => 'Percent off',
        'fixed'   => 'Fixed amount off',
    );
}

// "10% off" or "₱100.00 off"
function coupon_label($coupon)
{
    if ($coupon['discount_type'] === 'percent') {
        return rtrim(rtrim(number_format((float)$coupon['discount_value'], 2), '0'), '.') . '% off';
    }
    return peso($coupon['discount_value']) . ' off';
}

// a price as plain text ("₱ 1,250.00") for messages that are escaped before they are shown
function peso_text($amount)
{
    return "\u{20B1} " . number_format((float)$amount, 2);
}

// codes are saved and compared in capitals without spaces, so "welcome 10" finds WELCOME10
function normalize_coupon_code($code)
{
    return strtoupper(preg_replace('/\s+/', '', (string)$code));
}

// the coupon with this code, or null. $lock = true locks the row until the transaction ends,
// so two customers cannot both take the last use of a limited coupon
function find_coupon($conn, $code, $lock = false)
{
    $sql = "SELECT * FROM coupon WHERE code = ? LIMIT 1" . ($lock ? " FOR UPDATE" : "");
    $row = mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [normalize_coupon_code($code)]));
    return $row ?: null;
}

// how many orders used the coupon (cancelled orders do not count)
function coupon_times_used($conn, $coupon_id)
{
    $sql = "SELECT COUNT(*) AS total FROM orders WHERE coupon_id = ? AND status <> 'cancelled'";
    return (int)mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [$coupon_id]))['total'];
}

// why this customer cannot use the coupon on an order with this items total, or '' when they can
function coupon_error($conn, $coupon, $user_id, $itemsTotal)
{
    if (!$coupon) {
        return 'That coupon code does not exist.';
    }
    $today = date('Y-m-d');
    if ($coupon['status'] !== 'active') {
        return 'This coupon is no longer available.';
    }
    if ($coupon['starts_at'] !== null && $coupon['starts_at'] > $today) {
        return 'This coupon can be used from ' . date('F j, Y', strtotime($coupon['starts_at'])) . '.';
    }
    if ($coupon['expires_at'] !== null && $coupon['expires_at'] < $today) {
        return 'This coupon expired on ' . date('F j, Y', strtotime($coupon['expires_at'])) . '.';
    }
    if ((float)$itemsTotal < (float)$coupon['min_order']) {
        return 'This coupon needs an order of at least ' . peso_text($coupon['min_order'])
            . '. Add ' . peso_text((float)$coupon['min_order'] - (float)$itemsTotal) . ' more to use it.';
    }
    if ($coupon['max_uses'] !== null && coupon_times_used($conn, $coupon['coupon_id']) >= (int)$coupon['max_uses']) {
        return 'This coupon has been used up.';
    }
    $sql = "SELECT COUNT(*) AS total FROM orders WHERE coupon_id = ? AND user_id = ? AND status <> 'cancelled'";
    if ((int)mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [$coupon['coupon_id'], $user_id]))['total'] > 0) {
        return 'You have already used this coupon.';
    }
    return '';
}

// how much the coupon takes off an order with this items total (never more than the total)
function coupon_discount($coupon, $itemsTotal)
{
    $itemsTotal = (float)$itemsTotal;
    if ($coupon['discount_type'] === 'percent') {
        $discount = round($itemsTotal * (float)$coupon['discount_value'] / 100, 2);
    } else {
        $discount = (float)$coupon['discount_value'];
    }
    return min($discount, $itemsTotal);
}

// a Y-m-d date typed in a form, or null when it is not a real date
function form_date($value)
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return ($date && $date->format('Y-m-d') === $value) ? $value : null;
}

// check the admin's coupon form (all in PHP). $ignoreId lets a coupon keep its own code when editing.
// returns array($data, $errors, $old): $data is ready to save, $old is what was typed
function validate_coupon_input($conn, $ignoreId = 0)
{
    $old = array(
        'code' => normalize_coupon_code(post_value('code')),
        'description' => strip_tags(post_value('description')),
        'discount_type' => post_value('discount_type'),
        'discount_value' => post_value('discount_value'),
        'min_order' => post_value('min_order'),
        'max_uses' => post_value('max_uses'),
        'starts_at' => post_value('starts_at'),
        'expires_at' => post_value('expires_at'),
        'status' => post_value('status'),
    );
    $errors = array();

    if ($old['code'] === '') {
        $errors['code'] = 'Please enter a coupon code.';
    } else if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $old['code'])) {
        $errors['code'] = 'Use 3 to 30 letters, numbers, dashes or underscores.';
    } else if (mysqli_num_rows(mysqli_execute_query($conn,
        "SELECT coupon_id FROM coupon WHERE code = ? AND coupon_id <> ? LIMIT 1", [$old['code'], $ignoreId])) > 0) {
        $errors['code'] = 'Another coupon already uses this code.';
    }

    if (mb_strlen($old['description']) > 255) {
        $errors['description'] = 'The description should be 255 characters or less.';
    }

    if (!isset(coupon_types()[$old['discount_type']])) {
        $errors['discount_type'] = 'Please choose the kind of discount.';
    }

    if ($old['discount_value'] === '' || !is_numeric($old['discount_value']) || (float)$old['discount_value'] <= 0) {
        $errors['discount_value'] = 'Please enter a discount greater than 0.';
    } else if ($old['discount_type'] === 'percent' && (float)$old['discount_value'] > 100) {
        $errors['discount_value'] = 'A percent discount cannot be more than 100.';
    } else if ((float)$old['discount_value'] > 99999999) {
        $errors['discount_value'] = 'That discount is too large.';
    }

    if ($old['min_order'] !== '' && (!is_numeric($old['min_order']) || (float)$old['min_order'] < 0 || (float)$old['min_order'] > 99999999)) {
        $errors['min_order'] = 'Please enter 0 or more, or leave it empty.';
    }

    if ($old['max_uses'] !== '' && filter_var($old['max_uses'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 1000000))) === false) {
        $errors['max_uses'] = 'Please enter a whole number of 1 or more, or leave it empty for no limit.';
    }

    $startsAt = null;
    if ($old['starts_at'] !== '') {
        $startsAt = form_date($old['starts_at']);
        if ($startsAt === null) {
            $errors['starts_at'] = 'Please enter a valid date.';
        }
    }
    $expiresAt = null;
    if ($old['expires_at'] !== '') {
        $expiresAt = form_date($old['expires_at']);
        if ($expiresAt === null) {
            $errors['expires_at'] = 'Please enter a valid date.';
        } else if ($startsAt !== null && $expiresAt < $startsAt) {
            $errors['expires_at'] = 'The end date must be on or after the start date.';
        }
    }

    if (!in_array($old['status'], array('active', 'inactive'), true)) {
        $errors['status'] = 'Please choose a status.';
    }

    $data = array(
        'code' => $old['code'],
        'description' => $old['description'] === '' ? null : $old['description'],
        'discount_type' => $old['discount_type'],
        'discount_value' => round((float)$old['discount_value'], 2),
        'min_order' => $old['min_order'] === '' ? 0.0 : round((float)$old['min_order'], 2),
        'max_uses' => $old['max_uses'] === '' ? null : (int)$old['max_uses'],
        'starts_at' => $startsAt,
        'expires_at' => $expiresAt,
        'status' => $old['status'],
    );
    return array($data, $errors, $old);
}

// the badge for a coupon in the admin list: Active, Scheduled, Expired, Used up or Inactive
function coupon_state_badge($coupon, $timesUsed)
{
    $today = date('Y-m-d');
    if ($coupon['status'] !== 'active') {
        return "<span class='badge bg-secondary'>Inactive</span>";
    }
    if ($coupon['expires_at'] !== null && $coupon['expires_at'] < $today) {
        return "<span class='badge bg-light text-dark border'>Expired</span>";
    }
    if ($coupon['max_uses'] !== null && $timesUsed >= (int)$coupon['max_uses']) {
        return "<span class='badge bg-light text-dark border'>Used up</span>";
    }
    if ($coupon['starts_at'] !== null && $coupon['starts_at'] > $today) {
        return "<span class='badge bg-info text-dark'>Scheduled</span>";
    }
    return "<span class='badge bg-success'>Active</span>";
}
