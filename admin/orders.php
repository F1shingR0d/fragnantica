<?php
// SESSION + INCLUDES: start the session, connect, admin only, show the navbar
session_start();
include('../includes/config.php');
include('../includes/adminCheck.php');
include('../includes/header.php');

// SELECT query (view): all orders with their totals
$sql = "SELECT order_id, name, email, status, payment_method, reference_no, date_placed, items, total FROM order_totals_view ORDER BY order_id DESC";
$result = mysqli_query($conn, $sql);
$orderCount = mysqli_num_rows($result);
?>
<div class="container mt-4">
    <?php include('../includes/alert.php'); ?>
    <h2>number of orders <?= $orderCount ?></h2>
    <!-- HTML TABLE: all orders with a view link -->
    <div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Date placed</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // WHILE loop: one table row per order
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>{$row['order_id']}</td>";
                echo "<td>{$row['name']}</td>";
                echo "<td>{$row['email']}</td>";
                echo "<td>{$row['date_placed']}</td>";
                echo "<td>{$row['items']}</td>";
                echo "<td>{$row['total']}</td>";
                echo "<td>{$row['payment_method']} {$row['reference_no']}</td>";
                echo "<td>{$row['status']}</td>";
                echo "<td><a href='orderDetails.php?id={$row['order_id']}' class='action-icon' aria-label='View order'><i class='fa-regular fa-eye'></i></a></td>";
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
