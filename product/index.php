<?php
// SESSION + INCLUDES: start the session, connect, admin only, show the navbar
session_start();
include('../includes/config.php');
include('../includes/adminCheck.php');
include('../includes/header.php');

// SELECT query (JOIN): all products with their category and stock
$sql = "SELECT p.product_id, p.name, p.brand, p.size, p.img_path, p.cost_price, p.sell_price, c.name AS category, s.quantity
        FROM product p INNER JOIN category c USING (category_id) INNER JOIN stock s USING (product_id)
        ORDER BY p.product_id DESC";
$result = mysqli_query($conn, $sql);
$itemCount = mysqli_num_rows($result);
?>
<div class="container mt-4">
    <?php include('../includes/alert.php'); ?>
    <a href="create.php" class="btn btn-primary btn-lg" role="button">Add product</a>
    <h2 class="mt-3">number of products <?= $itemCount ?></h2>
    <!-- HTML TABLE: product list with edit and delete links -->
    <div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Id</th>
                <th>Name</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Size</th>
                <th>Cost price</th>
                <th>Sell price</th>
                <th>Stock</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // WHILE loop: one table row per product
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td><img src='{$row['img_path']}' width='80' height='80' /></td>";
                echo "<td>{$row['product_id']}</td>";
                echo "<td><a href='view.php?id={$row['product_id']}'>{$row['name']}</a></td>";
                echo "<td>{$row['brand']}</td>";
                echo "<td>{$row['category']}</td>";
                echo "<td>{$row['size']}</td>";
                echo "<td>{$row['cost_price']}</td>";
                echo "<td>{$row['sell_price']}</td>";
                echo "<td>{$row['quantity']}</td>";
                // HTML DETAILS: the trash icon opens a "Yes, delete" link instead of deleting right away
                echo "<td class='actions'><a href='edit.php?id={$row['product_id']}' class='action-icon' aria-label='Edit'><i class='fa-regular fa-pen-to-square'></i></a>
                    <details class='confirm'>
                        <summary class='action-icon icon-danger' aria-label='Delete'><i class='fa-solid fa-trash'></i></summary>
                        <div class='confirm-box'>Delete this product? <a href='delete.php?id={$row['product_id']}' class='btn btn-sm btn-danger'>Yes, delete</a></div>
                    </details></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
</div>
<?php
include('../includes/footer.php');
?>
