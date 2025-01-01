<?php
session_start();

if (!isset($_SESSION['password'])) {
    header('Location: ../login');
    exit();
}

include 'partials/base.php';
include 'partials/header.php';
require_once 'libs/WebAuthn-2.2.0/JsonManager.php';

const DEFAULT_USER_ID = '01';
$registrations = getWebauthnRegistrations(DEFAULT_USER_ID);
?>
<h2>Register new passkey</h2>
<div class="error-box"></div>
<button type="button" class="button button_spinner" onclick="register()">
    <span class="button__text">Register a new passkey</span>
</button>

<table>
    <tr>
        <th>Device</th>
        <th>Registration Date</th>
        <th>Action</th>
    </tr>

    <?php foreach ($registrations as $registration) { ?>
    <tr>
        <td><?= $registration['deviceDisplayName'] ?></td>
        <td><?= date('Y-d-m H:i:s', $registration['registrationDate']) ?> (UTC)</td>
        <td>
        <button type="button" class="button">
            <span class="button__text">Remove</span>
        </button>
        </td>
    </tr>
    <?php } ?>
</table>

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
