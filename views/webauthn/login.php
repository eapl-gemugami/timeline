<?php
# TODO: Check that the user is logged in

if (!isset($_SESSION['password'])) {
    # Redirect to login
}

include 'partials/header_no_twts.php';
?>
<h2>Login with passkey</h2>
<div class="error-box"></div>
<button type="button" class="button button_spinner" onclick="login()">
    <span class="button__text">Login with Passkey</span>
</button>

<script src="../libs/webauthn.js"></script>
<script>
// Spinner button based on: https://dev.to/dcodeyt/create-a-button-with-a-loading-spinner-in-html-css-1c0h

let btnLoginEnabled = true;

async function login() {
    if (btnLoginEnabled) {
        btnLoginEnabled = false;
        const btn = document.querySelector(".button");
        btn.classList.add("button--loading");

        const result = await checkRegistration();
        if (result === true) {
            window.location.replace("../");
        } else {
            btn.classList.remove("button--loading");
            btnLoginEnabled = true;
        }
    }
}
</script>

<?php
include 'partials/footer.php';
