<?php
require_once "partials/base.php";

# TODO: Check that the user is logged in

include 'partials/header.php';
?>
<h2>Enter password or TOTP</h2>
<form method="post" action="" id="login_form">
    <input type="password" name="pass" placeholder="Password" autofocus><br>
    <input type="submit" name="submit_pass" value="Login">
</form>
<?php
include 'partials/footer.php';
