<?php
session_start();
include('./includes/config.php');
include('./includes/header.php');

// search by perfume name or brand
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $keyword = trim($_GET['search']);
    $sql = "SELECT perfume_id, name, brand, scent_type, size_ml, sell_price, img_path FROM perfume WHERE name LIKE ? OR brand LIKE ? ORDER BY perfume_id DESC";
    $result = mysqli_execute_query($conn, $sql, ["%{$keyword}%", "%{$keyword}%"]);
} else {
    $keyword = '';
    $sql = "SELECT perfume_id, name, brand, scent_type, size_ml, sell_price, img_path FROM perfume ORDER BY perfume_id DESC";
    $result = mysqli_query($conn, $sql);
}

$perfumeCount = mysqli_num_rows($result);

?>
<div class="container mt-3">
    <?php include('./includes/alert.php'); ?>
</div>
<h1 align="center">Perfumes</h1>

<?php
if ($keyword !== '') {
    $safeKeyword = htmlentities($keyword);
    echo "<p align='center'>Search results for <strong>\"{$safeKeyword}\"</strong>: {$perfumeCount} found. <a href='index.php'>Show all perfumes</a></p>";
}

if ($perfumeCount > 0) {
    $products_item = '<ul class="products">';

    //fetch each perfume and output HTML
    while ($row = mysqli_fetch_assoc($result)) {
        $price = number_format($row['sell_price'], 2);
        $products_item .= <<<EOT
    <li class="product">
        <div class="product-content">
            <h3>{$row['name']}</h3>
            <div class="product-thumb"><img src="./perfume/{$row['img_path']}" width="150" height="150" alt="{$row['name']}"></div>
            <div class="product-info">
                <p class="brand">{$row['brand']}</p>
                <p>{$row['scent_type']} &middot; {$row['size_ml']} ml</p>
                <p class="price">&#8369; {$price}</p>
            </div>
        </div>
    </li>
EOT;
    }

    $products_item .= '</ul>';
    echo $products_item;
} else if ($keyword !== '') {
    echo "<p align='center'>No perfumes match your search.</p>";
} else {
    echo "<p align='center'>No perfumes available yet.</p>";
}

include('./includes/footer.php');
?>
