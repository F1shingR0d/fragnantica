<?php
// SESSION + INCLUDES: start the session, connect, load functions (no login needed)
session_start();
include('../includes/config.php');
include('../includes/functions.php');

// SELECT query (JOIN): load the product, go home if it does not exist
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$sql = "SELECT p.*, c.name AS category, s.quantity AS qty
        FROM product p INNER JOIN category c USING (category_id) INNER JOIN stock s USING (product_id)
        WHERE p.product_id = ? LIMIT 1";
$result = mysqli_execute_query($conn, $sql, [$product_id]);
$product = mysqli_fetch_assoc($result);
if (!$product) {
    $_SESSION['message'] = 'product not found';
    header("Location: ../index.php");
    exit();
}

// SELECT query: the extra photos
$photo_result = mysqli_execute_query($conn, "SELECT img_path FROM product_images WHERE product_id = ?", [$product_id]);

// SELECT query (JOIN): the reviews with the reviewer's name and photo
$sql = "SELECT r.user_id, r.rating, r.comment, r.created_at, r.updated_at, u.name, u.img_path
        FROM review r INNER JOIN users u USING (user_id)
        WHERE r.product_id = ? ORDER BY r.created_at DESC";
$review_result = mysqli_execute_query($conn, $sql, [$product_id]);
$reviewCount = mysqli_num_rows($review_result);

// IF block + AVG query: the average rating
$average = 0;
if ($reviewCount > 0) {
    $sql = "SELECT AVG(rating) AS average FROM review WHERE product_id = ?";
    $row = mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [$product_id]));
    $average = round($row['average'], 1);
}

// IF block + SELECT queries: can this user review, and do they already have a review
$canReview = false;
$myReview = null;
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT order_id FROM order_details_view WHERE user_id = ? AND product_id = ? AND status = 'completed' LIMIT 1";
    $result = mysqli_execute_query($conn, $sql, [$_SESSION['user_id'], $product_id]);
    $canReview = mysqli_num_rows($result) > 0;

    $sql = "SELECT rating, comment FROM review WHERE user_id = ? AND product_id = ? LIMIT 1";
    $myReview = mysqli_fetch_assoc(mysqli_execute_query($conn, $sql, [$_SESSION['user_id'], $product_id]));
}

include('../includes/header.php');
?>
<div class="container mt-4">
    <?php include('../includes/alert.php'); ?>
    <!-- PRODUCT DETAILS: photos on the left, info and Add to Cart on the right -->
    <div class="row">
        <div class="col-md-5">
            <img src="<?php echo htmlspecialchars($product['img_path']); ?>" class="product-photo">
            <div class="mt-2">
                <?php
                // WHILE loop: the extra photos, click one to see it bigger
                while ($photo = mysqli_fetch_assoc($photo_result)) {
                    echo "<a href='{$photo['img_path']}' target='_blank'><img src='{$photo['img_path']}' class='extra-photo me-2 mb-2'></a>";
                }
                ?>
            </div>
        </div>
        <div class="col-md-7">
            <p class="text-muted mb-1"><?php echo htmlspecialchars($product['category']); ?></p>
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p><strong><?php echo htmlspecialchars($product['brand']); ?></strong> &middot; <?php echo htmlspecialchars($product['size']); ?></p>
            <p class="price fs-4"><?php echo peso($product['sell_price']); ?></p>
            <p><?php echo htmlspecialchars($product['description']); ?></p>
            <?php
            // IF block: average rating and number of reviews
            if ($reviewCount > 0) {
                echo "<p><span class='stars'><i class='fa-solid fa-star'></i></span> {$average} out of 5 ({$reviewCount} reviews)</p>";
            }

            // IF-ELSE block: Add to Cart form when in stock, otherwise "Out of stock"
            if ($product['qty'] > 0) {
                echo "<p class='text-success'>{$product['qty']} in stock</p>";
                echo "<form method='POST' action='../cart/cart_update.php'>
                    <label>Quantity <input type='text' size='2' name='product_qty' value='1' /></label>
                    <input type='hidden' name='product_id' value='{$product['product_id']}' />
                    <input type='hidden' name='type' value='add' />
                    <button type='submit' class='btn btn-primary'>Add to Cart</button>
                </form>";
            } else {
                echo "<p class='text-danger'>Out of stock</p>";
            }
            ?>
        </div>
    </div>

    <!-- REVIEWS SECTION: review form on the left, review list on the right -->
    <h3 class="mt-5">Customer Reviews</h3>
    <div class="row">
        <div class="col-md-5">
            <?php
            // IF-ELSE IF-ELSE block: review form, "only buyers" note, or login link
            if ($canReview) {
                $rating = 0;
                $comment = '';
                $button = 'Post review';
                if ($myReview) {
                    $rating = $myReview['rating'];
                    $comment = $myReview['comment'];
                    $button = 'Update review';
                }
                if (isset($_SESSION['reviewRating'])) {
                    $rating = $_SESSION['reviewRating'];
                    $comment = $_SESSION['reviewComment'];
                    unset($_SESSION['reviewRating'], $_SESSION['reviewComment']);
                }
            ?>
                <div class="card mb-3">
                    <div class="card-header"><?php echo $myReview ? 'Update your review' : 'Write a review'; ?></div>
                    <div class="card-body">
                        <form method="POST" action="../review/store.php" novalidate>
                            <label for="rating">Rating</label>
                            <select name="rating" id="rating" class="form-control">
                                <option value="">Choose a rating</option>
                                <?php
                                // FOR loop: rating options from 5 down to 1
                                for ($i = 5; $i >= 1; $i--) {
                                    if ($rating == $i) {
                                        echo "<option value='{$i}' selected>{$i} star(s)</option>";
                                    } else {
                                        echo "<option value='{$i}'>{$i} star(s)</option>";
                                    }
                                }
                                ?>
                            </select>
                            <small class="text-danger"><?php
                                if (isset($_SESSION['ratingError'])) {
                                    echo $_SESSION['ratingError'];
                                    unset($_SESSION['ratingError']);
                                }
                                ?></small><br>
                            <label for="comment">Your review</label>
                            <textarea name="comment" id="comment" class="form-control" rows="4"><?php echo htmlspecialchars($comment); ?></textarea>
                            <small class="text-danger"><?php
                                if (isset($_SESSION['commentError'])) {
                                    echo $_SESSION['commentError'];
                                    unset($_SESSION['commentError']);
                                }
                                ?></small><br>
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>" />
                            <button type="submit" class="btn btn-primary" name="submit"><?php echo htmlspecialchars($button); ?></button>
                        </form>
                    </div>
                </div>
            <?php
            } else if (isset($_SESSION['user_id'])) {
                echo "<p class='text-muted'>Only customers who bought this product can review it, once the order is completed.</p>";
            } else {
                echo "<p class='text-muted'><a href='../user/login.php'>Login</a> to review a product you bought.</p>";
            }
            ?>
        </div>
        <div class="col-md-7">
            <?php
            // IF block: message when there are no reviews
            if ($reviewCount === 0) {
                echo "<p class='text-muted'>No reviews yet.</p>";
            }
            // WHILE loop: one review with stars, photo and date
            while ($row = mysqli_fetch_assoc($review_result)) {
                $stars = '';
                // FOR loop: filled and empty stars
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $row['rating']) {
                        $stars .= "<i class='fa-solid fa-star'></i>";
                    } else {
                        $stars .= "<i class='fa-regular fa-star'></i>";
                    }
                }
                $photo = "<i class='fa-solid fa-user'></i>";
                if (!empty($row['img_path'])) {
                    $photo = "<img src='../user/{$row['img_path']}' class='nav-photo'>";
                }
                $edited = '';
                if ($row['updated_at']) {
                    $edited = "(edited {$row['updated_at']})";
                }
                echo "<div class='review'>
                    <p class='mb-1'>{$photo} <strong>{$row['name']}</strong> <span class='stars'>{$stars}</span></p>
                    <p class='mb-1'>{$row['comment']}</p>
                    <small class='text-muted'>{$row['created_at']} {$edited}</small>
                </div>";
            }
            ?>
        </div>
    </div>
</div>
<?php
include('../includes/footer.php');
?>
