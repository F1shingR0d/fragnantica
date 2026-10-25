<?php
$date = date('Y');
$store = htmlspecialchars(STORE_NAME, ENT_QUOTES, 'UTF-8');
$contact = htmlspecialchars(STORE_EMAIL, ENT_QUOTES, 'UTF-8');
print "<footer class='text-center text-muted mt-5 mb-3'>copyright {$date} {$store} &middot; <a href='mailto:{$contact}' class='text-muted'>{$contact}</a></footer>";
?>


</body>
</html>
