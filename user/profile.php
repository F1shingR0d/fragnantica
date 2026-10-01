<?php
session_start();
include("../includes/config.php");

// only logged in users can open their profile
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'please log in first';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['submit'])) {
    $name = trim(strip_tags($_POST['name']));
    $address = trim(strip_tags($_POST['address']));
    $town = trim(strip_tags($_POST['town']));
    $zipcode = trim($_POST['zipcode']);
    $phone = trim($_POST['phone']);

    //validation
    if ($name === '' || $address === '' || $town === '' || $zipcode === '' || $phone === '') {
        $_SESSION['message'] = 'please fill in all fields';
    } else if (!preg_match("/^[0-9]{4}$/", $zipcode)) {
        $_SESSION['message'] = 'zip code should be 4 digits';
    } else if (!preg_match("/^09[0-9]{9}$/", $phone)) {
        $_SESSION['message'] = 'phone number should be 11 digits and start with 09';
    } else {
        try {
            // the name is saved in the users table
            $sql = "UPDATE users SET name=? WHERE user_id=?";
            mysqli_execute_query($conn, $sql, [$name, $user_id]);

            // the delivery details are saved in the customer table
            // first time = INSERT, after that = UPDATE
            $sql = "SELECT customer_id FROM customer WHERE user_id=? LIMIT 1";
            $result = mysqli_execute_query($conn, $sql, [$user_id]);

            if (mysqli_num_rows($result) === 1) {
                $sql = "UPDATE customer SET addressline=?, town=?, zipcode=?, phone=? WHERE user_id=?";
            } else {
                $sql = "INSERT INTO customer (addressline, town, zipcode, phone, user_id) VALUES (?, ?, ?, ?, ?)";
            }
            // both queries use the values in the same order
            mysqli_execute_query($conn, $sql, [$address, $town, $zipcode, $phone, $user_id]);

            $_SESSION['name'] = $name;
            $_SESSION['success'] = 'profile saved';
            header("Location: profile.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            $_SESSION['message'] = 'could not save profile: ' . $e->getMessage();
        }
    }
}

// get the saved details (customer columns are empty if the profile is not filled in yet)
$sql = "SELECT u.name, u.email, c.addressline, c.town, c.zipcode, c.phone FROM users u LEFT JOIN customer c USING (user_id) WHERE u.user_id=? LIMIT 1";
$result = mysqli_execute_query($conn, $sql, [$user_id]);
$profile = mysqli_fetch_assoc($result);

// if saving failed, show what the user typed instead
if (isset($_POST['submit'])) {
    $profile['name'] = $name;
    $profile['addressline'] = $address;
    $profile['town'] = $town;
    $profile['zipcode'] = $zipcode;
    $profile['phone'] = $phone;
}

include("../includes/header.php");
?>

<div class="container-xl px-4 mt-4">
    <?php include("../includes/alert.php"); ?>
    <div class="row">
        <div class="col-xl-8 mx-auto">
            <div class="card mb-4">
                <div class="card-header">My Profile</div>
                <div class="card-body">
                    <p class="small text-muted">These details will be used as the delivery information for your orders.</p>
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="small mb-1" for="name">Name</label>
                                <input class="form-control" id="name" type="text" placeholder="Enter your name"
                                    name="name" value="<?php echo $profile['name']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="small mb-1" for="email">Email</label>
                                <input class="form-control" id="email" type="text" value="<?php echo $profile['email']; ?>" disabled>
                            </div>
                        </div>

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="small mb-1" for="address">Address</label>
                                <input class="form-control" id="address" type="text" placeholder="House no., street, barangay"
                                    name="address" value="<?php echo $profile['addressline']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="small mb-1" for="town">Town / City</label>
                                <input class="form-control" id="town" type="text" placeholder="Enter your town or city"
                                    name="town" value="<?php echo $profile['town']; ?>">
                            </div>
                        </div>

                        <div class="row gx-3 mb-3">
                            <div class="col-md-6">
                                <label class="small mb-1" for="zip">Zip Code</label>
                                <input class="form-control" id="zip" type="text" placeholder="e.g. 1630"
                                    name="zipcode" value="<?php echo $profile['zipcode']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="small mb-1" for="phone">Phone Number</label>
                                <input class="form-control" id="phone" type="tel" placeholder="e.g. 09171234567"
                                    name="phone" value="<?php echo $profile['phone']; ?>">
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit" name="submit">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include("../includes/footer.php");
?>
