<?php
require_once 'libs/WebAuthn-2.2.0/WebAuthn.php';

const RP_NAME = 'Timeline'; # Add site name or domain
const USER_ID = '01'; # For a single user instance we use a constant one
const TIMEOUT_SECS = 30;

const FILE_PATH = 'private/webauthn/secrets.json';

# From https://www.corbado.com/glossary/aaguid
const DEVICE_TYPE = [
    'AAAAAAAAAAAAAAAAAAAAAA==' => 'Generic or unspecified device',
    '1UiCbnm020Cj2BERb36DSQ==' => 'Bitwarden',
    '6puNZk0BHSE85La0jLV11A==' => 'Google Password Manager',
    'rc4AAjW8xgpkiwsl8fBVAw==' => 'Chrome on Mac',
    'CJhwWMrcS4G24TDeUNy+lg==' => 'Windows Hello',
    'nd0YF69aRnKiuT492VAAqQ==' => 'Windows Hello',
    'YCiwF7HUTAK0s6/Nr8lrsg==' => 'Windows Hello',
    '3U7CieAdQcm7iXD6hF1L8g==' => 'iCloud Keychain (Managed)',
    'UxEm1ucXQVyTID2appgSOQ==' => 'Dashlane',
    'utpVZqeqQB+9lkVhmlUSDQ==' => '1Password',
    'uE5ASBXcTdCGQPT2CBPIrw==' => 'NordPass',
    'DqJCtEPEShuLF91tC2uuxg==' => 'Keeper',
    '84CVQH8UScGos4+BOyJVQQ==' => 'Enpass',
    'tTl2ZkiFqmvOv+UiYqQ5og==' => 'Chromium Browser',
    'dxtI/dPUT3SSMvwVerBQeg==' => 'Edge on Mac',
    'OaVkfhhTRGyh9qebrp9bxw==' => 'IDmelon',
    '+/wwBxVOTsyMC24CBVfXvQ==' => 'iCloud Keychain',
    'U0FNU1VORwAAAAAAAAAAAA==' => 'Samsung Pass',
    'ZqDMs71qGR/uBuN1xQuYRg==' => 'Thales Bio iOS SDK',
    'iDYzavWQCSEwHUZCdTHu5g==' => 'Thales Bio Android SDK',
    'zWmttTx63rkxd2gA6my3Kg==' => 'Thales PIN Android SDK',
    'FykPHsISNNAUIzZdcp8J2Q==' => 'Thales PIN iOS SDK',
    'UHJvdG9uUGFzc1Byb3Rvbg==' => 'Proton Pass',
    '/bFBsl2ERD6KNUaYwgWlAg==' => 'KeePassXC',
];

function saveJsonToFile($filePath, $data) {
    # Convert PHP array or object to JSON string
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);
    if ($jsonData === false) {
        var_dump($data);
        die("Error encoding JSON: " . json_last_error_msg());
    }

    # Save JSON string to the file
    if (file_put_contents($filePath, $jsonData) === false) {
        die("Error writing to file: $filePath");
    }
}

function loadJsonFromFile($filePath) {
    # Read JSON string from the file
    $jsonData = file_get_contents($filePath);
    if ($jsonData === false) {
        # If doesn't exist, create an empty array
        return [];
    }

    # Convert JSON string back to PHP array
    $data = json_decode($jsonData, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        # If it's corrupted, return an empty array
        return [];
    }

    return $data;
}

function getWebauthnRegistrations($userId) {
    $registrations = loadJsonFromFile(FILE_PATH);
    $registrationsForUser = [];

    foreach ($registrations as $reg) {
        if ($reg['userId'] === $userId) {
            $reg['deviceDisplayName'] = DEVICE_TYPE[$reg['AAGUID']] ?? 'Unknown device';
            $registrationsForUser[] = $reg;
        }
    }

    return $registrationsForUser;
}