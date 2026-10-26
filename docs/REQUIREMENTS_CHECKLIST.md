# ITIM211 requirements checklist

Where each requirement is in the project and how to show it.

**Before the demo (on any computer):**

1. Copy the project folder into `C:\xampp\htdocs` (any folder name works, e.g. `C:\xampp\htdocs\Veloura`).
2. Start Apache and MySQL in the XAMPP Control Panel (PHP 8.0 or newer).
3. Open `http://localhost/Veloura` (use your folder name). The first time, the site goes to `setup.php`:
   click **Set up the database** and it imports `database.sql`. It also does this when the database is an old copy.
4. For the order emails, set up Mailtrap (`docs/EMAIL_SETUP.md`). `includes/mail_config.php` is not on GitHub,
   so copy it with the folder or make it again from `includes/mail_config.example.php`.

If MySQL has a password or another port on that computer, change it at the top of `includes/config.php`.

Accounts: admin `admin@veloura.test` / `admin123`, customer `customer@veloura.test` / `customer123`.

## MP1 Product/Service CRUD

| Requirement | Where |
|---|---|
| Product CRUD | `product/index.php`, `create.php`/`store.php`, `edit.php`/`update.php`, `delete.php`, `view.php` |
| Upload a single photo | Main photo, saved in `product/images/`, path in `product.img_path` |
| Upload/view multiple photos | "More Photos" (`photos[]`, multiple) on Add/Edit, saved in the `product_images` table. Edit has a "remove" checkbox per photo. `product/view.php` shows all photos |

## MP2 User CRUD

| Requirement | Where |
|---|---|
| User CRUD | `admin/users/`: `index.php`, `create.php`/`store.php`, `edit.php`/`update.php`, `delete.php` |
| Registration uploads a photo | `user/register.php`, `user/store.php` (saved in `user/images/`) |
| User updates profile with a photo | `user/profile.php` > Upload new image |
| Admin deactivates a user | `admin/users/status.php` (Deactivate/Activate buttons in the users list). `user/login.php` refuses inactive accounts |
| Admin updates an active user's role | `admin/users/update.php`. The role list is disabled for an inactive user and the server refuses the change |

## MP3 Login/logout and access control

| Requirement | Where |
|---|---|
| Login with email, logout | `user/login.php`, `user/logout.php` |
| Restrict ordinary users from CRUD pages | `includes/adminCheck.php`, included at the top of every product, order and user page and script |
| Redirect unauthenticated users with a message | `adminCheck.php` / `loginCheck.php`: "Please login first to access that page" |
| Admins can access CRUD pages/scripts | `adminCheck.php` lets `role = 'admin'` through |

## MP4 Review CRUD

| Requirement | Where |
|---|---|
| Only a customer who availed the product can review | `review/store.php` and `product/view.php` check `order_details_view` for a **completed** order with the product |
| Reviews on the product details page | `product/view.php` > Customer Reviews |
| User can update the review | Same form shows "Update review"; `review/store.php` updates it (one review per customer per product) |
| Regex filter for bad words | `review/store.php` calls `censor()` (in `includes/functions.php`): `preg_replace("/\b{$word}\w*\b/i", "****", ...)` |

## MP5 Form validation in PHP, no HTML5 validation

Login (`user/login.php`) and product create/update (`product/store.php`, `product/update.php`) are
checked in PHP. The forms use `novalidate` and plain `type="text"` inputs (no `required`, `type="email"`
or `type="number"`). Each error shows under its field.

## Term Test Lab

| Requirement | Where |
|---|---|
| Completed transaction uses prepared statements | `cart/checkout.php`: one transaction, `mysqli_prepare` / `mysqli_stmt_bind_param` |
| Admin views all orders and updates the status | `admin/orders.php`, `admin/orderDetails.php`, `admin/updateOrder.php` |
| Email the customer about the update | `admin/updateOrder.php` (PHPMailer) |
| Email lists the products, subtotals and grand total | Same file, the table in `$mail->Body` |
| Update the status of the order | `UPDATE orders SET status = ?` (cancelling returns the items to stock) |
| PDF receipt attachment | FPDF in `admin/updateOrder.php`, attached with `addStringAttachment` |
| Mailtrap | `includes/mail_config.php` (see `docs/EMAIL_SETUP.md`) |

## Unit 1 Database design

Every table has a single-column primary key, so there are no partial dependencies (2NF).
Repeating data has its own table: `product_images` (many photos per product), `order_items`
(many products per order), `review`, `customer` (delivery details), `category`, `stock`.

Views (`database.sql`):

* `order_details_view`: one row per product in an order, with the customer, address and **subtotal**.
* `order_totals_view`: one row per order with its **grand total**.

## Quiz 3 Search on the homepage

`index.php`: search box at the top of the home page (and in the navbar). Searches product name,
brand, category and description.

## User-defined functions

`includes/functions.php`, included after `config.php` on the pages that use it:

| Function | What it does | Used in |
|---|---|---|
| `isValidEmail($email)` | checks the email format with a regex | `user/login.php`, `user/store.php`, `admin/users/store.php`, `admin/users/update.php` |
| `isImage($type)` | checks if an uploaded file is a JPG or PNG | `user/store.php`, `user/profile.php`, `admin/users/store.php`, `admin/users/update.php`, `product/store.php`, `product/update.php` |
| `savePhoto($source, $fileName, $folder)` | saves an uploaded photo in `images/` and returns its path | same files as `isImage()` |
| `censor($text)` | replaces bad words with `****` | `review/store.php` |
| `peso($amount)` | shows an amount as `₱ 1,500.00` | `index.php`, `product/view.php`, `cart/view_cart.php`, `user/myorders.php`, `admin/orderDetails.php`, `admin/updateOrder.php` (email) |
