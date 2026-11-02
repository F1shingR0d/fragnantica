<?php
// SESSION + INCLUDES: start the session, connect, admin only, show the navbar
session_start();
include('../../includes/config.php');
include('../../includes/adminCheck.php');
include('../../includes/header.php');

// SELECT query: all users, newest first
$sql = "SELECT user_id, name, email, img_path, role, status, created_at FROM users ORDER BY user_id DESC";
$result = mysqli_query($conn, $sql);
$userCount = mysqli_num_rows($result);
?>
<div class="container mt-4">
    <?php include('../../includes/alert.php'); ?>
    <a href="create.php" class="btn btn-primary btn-lg" role="button">Add user</a>
    <h2 class="mt-3">number of users <?= $userCount ?></h2>
    <!-- HTML TABLE: users with edit, delete and activate/deactivate links -->
    <div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Id</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Date joined</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // WHILE loop: one table row per user
            while ($row = mysqli_fetch_assoc($result)) {
                // IF block: the user's photo, or a default icon
                $photo = "<i class='fa-solid fa-user fa-2x text-muted'></i>";
                if (!empty($row['img_path'])) {
                    $photo = "<img src='../../user/{$row['img_path']}' class='profile-photo' style='width: 50px; height: 50px;'>";
                }

                // IF-ELSE block: status badge and Activate/Deactivate button
                if ($row['status'] === 'active') {
                    $status = "<span class='badge bg-success'>active</span>";
                    $statusLink = "<a href='status.php?id={$row['user_id']}&status=inactive' class='btn btn-sm btn-outline-danger'>Deactivate</a>";
                } else {
                    $status = "<span class='badge bg-secondary'>inactive</span>";
                    $statusLink = "<a href='status.php?id={$row['user_id']}&status=active' class='btn btn-sm btn-outline-success'>Activate</a>";
                }

                echo "<tr>";
                echo "<td>{$photo}</td>";
                echo "<td>{$row['user_id']}</td>";
                echo "<td>{$row['name']}</td>";
                echo "<td>{$row['email']}</td>";
                echo "<td>{$row['role']}</td>";
                echo "<td>{$status}</td>";
                echo "<td>{$row['created_at']}</td>";
                // HTML DETAILS: the trash icon opens a "Yes, delete" link instead of deleting right away
                echo "<td class='actions'><a href='edit.php?id={$row['user_id']}' class='action-icon' aria-label='Edit'><i class='fa-regular fa-pen-to-square'></i></a>
                    <details class='confirm'>
                        <summary class='action-icon icon-danger' aria-label='Delete'><i class='fa-solid fa-trash'></i></summary>
                        <div class='confirm-box'>Delete this user? <a href='delete.php?id={$row['user_id']}' class='btn btn-sm btn-danger'>Yes, delete</a></div>
                    </details>
                    {$statusLink}</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
</div>
<?php
include('../../includes/footer.php');
?>
