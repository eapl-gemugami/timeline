<?php
# TODO: Check that the user is logged in

if (!isset($_SESSION['password'])) {
    # Redirect to login
}

include 'partials/header_no_twts.php';
?>
<h2>Register new passkey</h2>
<div class="error-box"></div>
<button type="button" class="button button_spinner" onclick="register()">
    <span class="button__text">Register a new passkey</span>
</button>

<script src="../libs/webauthn.js"></script>
<script>
// Spinner button based on: https://dev.to/dcodeyt/create-a-button-with-a-loading-spinner-in-html-css-1c0h

let btnRegisterEnabled = true;

async function register() {
    if (btnRegisterEnabled) {
        btnRegisterEnabled = false;
        const btn = document.querySelector(".button");
        btn.classList.add("button--loading");

        const result = await createRegistration();
        btn.classList.remove("button--loading");
        btnRegisterEnabled = true;
    }
}
</script>

<?php
include 'partials/footer.php';
