<?php
session_start();
include('../includes/config.php');

// only admins can edit perfumes
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'admin access only, please log in with an admin account';
    header("Location: ../user/login.php");
    exit();
}

$perfume_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$perfume_id) {
    header("Location: index.php");
    exit();
}

$sql = "SELECT p.*, s.quantity FROM perfume p INNER JOIN stock s USING (perfume_id) WHERE p.perfume_id = ? LIMIT 1";
$result = mysqli_execute_query($conn, $sql, [$perfume_id]);
$perfume = mysqli_fetch_assoc($result);

if (!$perfume) {
    $_SESSION['message'] = 'perfume not found';
    header("Location: index.php");
    exit();
}

// after a failed update show what the admin typed, otherwise show the saved values
$name = isset($_SESSION['perfumeName']) ? $_SESSION['perfumeName'] : $perfume['name'];
$brand = isset($_SESSION['brand']) ? $_SESSION['brand'] : $perfume['brand'];
$scentType = isset($_SESSION['scent']) ? $_SESSION['scent'] : $perfume['scent_type'];
$size = isset($_SESSION['size']) ? $_SESSION['size'] : $perfume['size_ml'];
$cost = isset($_SESSION['cost']) ? $_SESSION['cost'] : $perfume['cost_price'];
$sell = isset($_SESSION['sell']) ? $_SESSION['sell'] : $perfume['sell_price'];
$qty = isset($_SESSION['qty']) ? $_SESSION['qty'] : $perfume['quantity'];

// the typed values are only shown once
unset($_SESSION['perfumeName'], $_SESSION['brand'], $_SESSION['scent'], $_SESSION['size'], $_SESSION['cost'], $_SESSION['sell'], $_SESSION['qty']);

include('../includes/header.php');

$scentTypes = array('Floral', 'Woody', 'Fresh', 'Citrus', 'Fruity', 'Oriental', 'Aquatic', 'Gourmand', 'Spicy');

?>
<div class="container mt-4">
    <h2>Edit Perfume</h2>
    <?php include('../includes/alert.php'); ?>
    <form method="POST" action="update.php" enctype="multipart/form-data">
        <input type="hidden" name="perfume_id" value="<?php echo $perfume['perfume_id']; ?>" />

        <div class="row gx-3 mb-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Perfume Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" />
                <small class="text-danger"><?php
                        if (isset($_SESSION['nameError'])) {
                            echo $_SESSION['nameError'];
                            unset($_SESSION['nameError']);
                        }
                        ?></small>
            </div>
            <div class="col-md-6">
                <label for="brand" class="form-label">Brand</label>
                <input type="text" class="form-control" id="brand" name="brand" value="<?php echo $brand; ?>" />
                <small class="text-danger"><?php
                        if (isset($_SESSION['brandError'])) {
                            echo $_SESSION['brandError'];
                            unset($_SESSION['brandError']);
                        }
                        ?></small>
            </div>
        </div>

        <div class="row gx-3 mb-3">
            <div class="col-md-6">
                <label for="scent" class="form-label">Scent Type</label>
                <select class="form-select" id="scent" name="scent_type">
                    <option value="">-- choose scent type --</option>
                    <?php
                    foreach ($scentTypes as $scent) {
                        if ($scentType === $scent) {
                            echo "<option value='{$scent}' selected>{$scent}</option>";
                        } else {
                            echo "<option value='{$scent}'>{$scent}</option>";
                        }
                    }
                    ?>
                </select>
                <small class="text-danger"><?php
                        if (isset($_SESSION['scentError'])) {
                            echo $_SESSION['scentError'];
                            unset($_SESSION['scentError']);
                        }
                        ?></small>
            </div>
            <div class="col-md-6">
                <label for="size" class="form-label">Size (ml)</label>
                <input type="number" class="form-control" id="size" name="size_ml" value="<?php echo $size; ?>" />
                <small class="text-danger"><?php
                        if (isset($_SESSION['sizeError'])) {
                            echo $_SESSION['sizeError'];
                            unset($_SESSION['sizeError']);
                        }
                        ?></small>
            </div>
        </div>

        <div class="row gx-3 mb-3">
            <div class="col-md-4">
                <label for="cost" class="form-label">Cost Price</label>
                <input type="text" class="form-control" id="cost" name="cost_price" value="<?php echo $cost; ?>" />
                <small class="text-danger"><?php
                        if (isset($_SESSION['costError'])) {
                            echo $_SESSION['costError'];
                            unset($_SESSION['costError']);
                        }
                        ?></small>
            </div>
            <div class="col-md-4">
                <label for="sell" class="form-label">Selling Price</label>
                <input type="text" class="form-control" id="sell" name="sell_price" value="<?php echo $sell; ?>" />
                <small class="text-danger"><?php
                        if (isset($_SESSION['sellError'])) {
                            echo $_SESSION['sellError'];
                            unset($_SESSION['sellError']);
                        }
                        ?></small>
            </div>
            <div class="col-md-4">
                <label for="qty" class="form-label">Stock</label>
                <input type="number" class="form-control" id="qty" name="quantity" value="<?php echo $qty; ?>" />
                <small class="text-danger"><?php
                        if (isset($_SESSION['qtyError'])) {
                            echo $_SESSION['qtyError'];
                            unset($_SESSION['qtyError']);
                        }
                        ?></small>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Current Photo</label><br />
            <img src="<?php echo $perfume['img_path']; ?>" width="120" height="120" class="perfume-photo border mb-2" />
            <br />
            <label for="photo" class="form-label">New Photo (JPG or PNG only, leave empty to keep the current photo)</label>
            <input class="form-control" type="file" id="photo" name="img_path" accept=".jpg,.jpeg,.png" />
            <small class="text-danger"><?php
                    if (isset($_SESSION['imageError'])) {
                        echo $_SESSION['imageError'];
                        unset($_SESSION['imageError']);
                    }
                    ?></small>
        </div>

        <button type="submit" class="btn btn-primary" name="submit">Save Changes</button>
        <a href="index.php" role="button" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php
include('../includes/footer.php');
?>
