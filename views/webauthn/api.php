<?php
/*
 * Copyright (C) 2022 Lukas Buchs
 * license https://github.com/lbuchs/WebAuthn/blob/master/LICENSE MIT
 *
 * Server test script for WebAuthn library. Saves new registrations in session.
 *
 *            JAVASCRIPT            |          SERVER
 * ------------------------------------------------------------
 *
 *               REGISTRATION
 *
 *      window.fetch  ----------------->     getCreateArgs
 *                                                |
 *   navigator.credentials.create   <-------------'
 *           |
 *           '------------------------->     processCreate
 *                                                |
 *         alert ok or fail      <----------------'
 *
 * ------------------------------------------------------------
 *
 *              VALIDATION
 *
 *      window.fetch ------------------>      getGetArgs
 *                                                |
 *   navigator.credentials.get   <----------------'
 *           |
 *           '------------------------->      processGet
 *                                                |
 *         alert ok or fail      <----------------'
 *
 * ------------------------------------------------------------
 */

# TODO: 2024-12
# [x] Save keys into a file
# [x] Disable showing sensitive debug info
# [ ] Check that a user already registered can't register again

require_once 'libs/WebAuthn-2.2.0/WebAuthn.php';

const RP_NAME = 'Timeline'; # Add site name or domain

$filePath = 'private/webauthn/secrets.json';

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
    #echo "Data saved to $filePath\n";
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

try {
    session_start();

    // read get argument and post body
    $fn = filter_input(INPUT_GET, 'fn');
    $requireResidentKey = !!filter_input(INPUT_GET, 'requireResidentKey');
    $userVerification = filter_input(INPUT_GET, 'userVerification', FILTER_SANITIZE_SPECIAL_CHARS);

    $userId = filter_input(INPUT_GET, 'userId', FILTER_SANITIZE_SPECIAL_CHARS);
    $userName = filter_input(INPUT_GET, 'userName', FILTER_SANITIZE_SPECIAL_CHARS);
    $userDisplayName = filter_input(INPUT_GET, 'userDisplayName', FILTER_SANITIZE_SPECIAL_CHARS);

    $userId = $userId ? preg_replace('/[^0-9a-f]/i', '', $userId) : "";
    $userName = $userName ? preg_replace('/[^0-9a-z]/i', '', $userName) : "";
    $userDisplayName = $userDisplayName ? preg_replace('/[^0-9a-z öüäéèàÖÜÄÉÈÀÂÊÎÔÛâêîôû]/i', '', $userDisplayName) : "";

    $post = trim(file_get_contents('php://input'));
    if ($post) {
        $post = json_decode($post, null, 512, JSON_THROW_ON_ERROR);
    }

    if ($fn !== 'getStoredDataHtml') {
        // TODO: Disable this function completely
        // Formats
        $formats = [];
        if (filter_input(INPUT_GET, 'fmt_android-key')) {
            $formats[] = 'android-key';
        }
        if (filter_input(INPUT_GET, 'fmt_android-safetynet')) {
            $formats[] = 'android-safetynet';
        }
        if (filter_input(INPUT_GET, 'fmt_apple')) {
            $formats[] = 'apple';
        }
        if (filter_input(INPUT_GET, 'fmt_fido-u2f')) {
            $formats[] = 'fido-u2f';
        }
        if (filter_input(INPUT_GET, 'fmt_none')) {
            $formats[] = 'none';
        }
        if (filter_input(INPUT_GET, 'fmt_packed')) {
            $formats[] = 'packed';
        }
        if (filter_input(INPUT_GET, 'fmt_tpm')) {
            $formats[] = 'tpm';
        }

        $rpId = 'localhost';
        if (filter_input(INPUT_GET, 'rpId')) {
            $rpId = filter_input(INPUT_GET, 'rpId', FILTER_VALIDATE_DOMAIN);
            if ($rpId === false) {
                throw new Exception('invalid relying party ID');
            }
        }

        // types selected on front end
        $typeUsb = !!filter_input(INPUT_GET, 'type_usb');
        $typeNfc = !!filter_input(INPUT_GET, 'type_nfc');
        $typeBle = !!filter_input(INPUT_GET, 'type_ble');
        $typeInt = !!filter_input(INPUT_GET, 'type_int');
        $typeHyb = !!filter_input(INPUT_GET, 'type_hybrid');

        // cross-platform: true, if type internal is not allowed
        //                 false, if only internal is allowed
        //                 null, if internal and cross-platform is allowed
        $crossPlatformAttachment = null;
        if (($typeUsb || $typeNfc || $typeBle || $typeHyb) && !$typeInt) {
            $crossPlatformAttachment = true;
        } else if (!$typeUsb && !$typeNfc && !$typeBle && !$typeHyb && $typeInt) {
            $crossPlatformAttachment = false;
        }

        // new Instance of the server library.
        // make sure that $rpId is the domain name.
        $WebAuthn = new lbuchs\WebAuthn\WebAuthn(RP_NAME, $rpId, $formats);

        // add root certificates to validate new registrations
        /*
        if (filter_input(INPUT_GET, 'solo')) {
            $WebAuthn->addRootCertificates('rootCertificates/solo.pem');
            $WebAuthn->addRootCertificates('rootCertificates/solokey_f1.pem');
            $WebAuthn->addRootCertificates('rootCertificates/solokey_r1.pem');
        }
        if (filter_input(INPUT_GET, 'apple')) {
            $WebAuthn->addRootCertificates('rootCertificates/apple.pem');
        }
        if (filter_input(INPUT_GET, 'yubico')) {
            $WebAuthn->addRootCertificates('rootCertificates/yubico.pem');
        }
        if (filter_input(INPUT_GET, 'hypersecu')) {
            $WebAuthn->addRootCertificates('rootCertificates/hypersecu.pem');
        }
        if (filter_input(INPUT_GET, 'google')) {
            $WebAuthn->addRootCertificates('rootCertificates/globalSign.pem');
            $WebAuthn->addRootCertificates('rootCertificates/googleHardware.pem');
        }
        if (filter_input(INPUT_GET, 'microsoft')) {
            $WebAuthn->addRootCertificates('rootCertificates/microsoftTpmCollection.pem');
        }
        if (filter_input(INPUT_GET, 'mds')) {
            $WebAuthn->addRootCertificates('rootCertificates/mds');
        }
        */
    }

    // ------------------------------------
    // request for create arguments
    // ------------------------------------
    if ($fn === 'getCreateArgs') {
        $timeoutSecs = 30;

        $excludeCredentialIds = [];
        # TODO: Dummy example, read this from DB
        $excludeCredentialIds[] = base64_decode('dmRmLs2BT6eTtm07aIIsiA==');

        $createArgs = $WebAuthn->getCreateArgs(\hex2bin($userId), $userName, $userDisplayName, $timeoutSecs, $requireResidentKey, $userVerification, $crossPlatformAttachment, $excludeCredentialIds);

        # It seems that the binary format is not supported, so we convert it to Hex
        $createArgs->publicKey->user->id = $createArgs->publicKey->user->id->getHex();

        header('Content-Type: application/json');

        print(json_encode($createArgs));

        // Save challange to session. You have to deliver it to processGet later.
        $_SESSION['challenge'] = $WebAuthn->getChallenge();

        // ------------------------------------
        // request for get arguments
        // ------------------------------------

    } else if ($fn === 'getGetArgs') {
        $ids = [];

        if ($requireResidentKey) {
            if (!isset($_SESSION['registrations']) || !is_array($_SESSION['registrations']) || count($_SESSION['registrations']) === 0) {
                throw new Exception('we do not have any registrations in session to check the registration');
            }
        } else {
            // load registrations from session stored there by processCreate.
            // normaly you have to load the credential Id's for a username
            // from the database.
            if (isset($_SESSION['registrations']) && is_array($_SESSION['registrations'])) {
                foreach ($_SESSION['registrations'] as $reg) {
                    if ($reg->userId === $userId) {
                        $ids[] = $reg->credentialId;
                    }
                }
            }

            if (count($ids) === 0) {
                throw new Exception("no registrations in session for userId $userId");
            }
        }

        $getArgs = $WebAuthn->getGetArgs($ids, 60 * 4, $typeUsb, $typeNfc, $typeBle, $typeHyb, $typeInt, $userVerification);

        header('Content-Type: application/json');
        print(json_encode($getArgs));

        // save challange to session. you have to deliver it to processGet later.
        $_SESSION['challenge'] = $WebAuthn->getChallenge();


        // ------------------------------------
        // process create
        // ------------------------------------

    } else if ($fn === 'processCreate') {
        $clientDataJSON = base64_decode($post->clientDataJSON);
        $attestationObject = base64_decode($post->attestationObject);
        $challenge = $_SESSION['challenge'];

        // processCreate returns data to be stored for future logins.
        $data = $WebAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, $userVerification === 'required', true, false);

        // Check if the AAGUID for that user already exists

        // Add user info
        $data->userId = $userId;
        $data->userName = $userName;
        $data->userDisplayName = $userDisplayName;

        $data->credentialId = base64_encode($data->credentialId);
        $data->AAGUID = base64_encode($data->AAGUID);

        $jsonData = loadJsonFromFile($filePath);

        # NOTE: Checking if the device is already registered was removed
        # But perhaps it'll need to be added again for some devices.

        $jsonData[] = $data;
            saveJsonToFile($filePath, $jsonData);

        $msg = 'Registration success.';
        if ($data->rootValid === false) {
            $msg = 'Registration ok, but certificate does not match any of the selected Root CA.';
        }

        $return = new stdClass();
        $return->success = true;
        $return->msg = $msg;

        header('Content-Type: application/json');
        print(json_encode($return));

        // ------------------------------------
        // proccess get
        // ------------------------------------

    } else if ($fn === 'processGet') {
        $clientDataJSON = base64_decode($post->clientDataJSON);
        $authenticatorData = base64_decode($post->authenticatorData);
        $signature = base64_decode($post->signature);
        $userHandle = base64_decode($post->userHandle);
        #$id = base64_decode($post->id);
        $challenge = $_SESSION['challenge'] ?? '';
        $credentialPublicKey = null;

        // looking up correspondending public key of the credential id
        // you should also validate that only ids of the given user name
        // are taken for the login.

        $registrations = loadJsonFromFile($filePath);
        foreach ($registrations as $reg) {
            if ($reg['credentialId'] === $post->id) {
                $credentialPublicKey = $reg['credentialPublicKey'];
                break;
            }
        }

        if ($credentialPublicKey === null) {
            throw new Exception('Public Key for credential ID not found!');
        }

        // if we have resident key, we have to verify that the userHandle is the provided userId at registration
        if ($requireResidentKey && $userHandle !== hex2bin($reg['userId'])) {
            throw new \Exception('userId doesnt match (is ' . bin2hex($userHandle) . ' but expect ' . $reg->userId . ')');
        }

        // process the get request. throws WebAuthnException if it fails
        $WebAuthn->processGet($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey, $challenge, null, $userVerification === 'required');

        $return = new stdClass();
        $return->success = true;

        header('Content-Type: application/json');
        print(json_encode($return));

        // ------------------------------------
        // proccess clear registrations
        // ------------------------------------

    } else if ($fn === 'clearRegistrations') {
        $_SESSION['registrations'] = null;
        $_SESSION['challenge'] = null;

        $return = new stdClass();
        $return->success = true;
        $return->msg = 'all registrations deleted';

        header('Content-Type: application/json');
        print(json_encode($return));

        // ------------------------------------
        // display stored data as HTML
        // ------------------------------------

    } else if ($fn === 'getStoredDataHtml') {
        /*
        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html><head><style>tr:nth-child(even){background-color: #f2f2f2;}</style></head>';
        $html .= '<body style="font-family:sans-serif">';
        if (isset($_SESSION['registrations']) && is_array($_SESSION['registrations'])) {
            $html .= '<p>There are ' . count($_SESSION['registrations']) . ' registrations in this session:</p>';
            foreach ($_SESSION['registrations'] as $reg) {
                $html .= '<table style="border:1px solid black;margin:10px 0;">';
                foreach ($reg as $key => $value) {

                    if (is_bool($value)) {
                        $value = $value ? 'yes' : 'no';
                    } else if (is_null($value)) {
                        $value = 'null';
                    } else if (is_object($value)) {
                        $value = chunk_split(strval($value), 64);
                    } else if (is_string($value) && strlen($value) > 0 && htmlspecialchars($value, ENT_QUOTES) === '') {
                        $value = chunk_split(bin2hex($value), 64);
                    }
                    $html .= '<tr><td>' . htmlspecialchars($key) . '</td><td style="font-family:monospace;">' . nl2br(htmlspecialchars($value)) . '</td>';
                }
                $html .= '</table>';
            }
        } else {
            $html .= '<p>There are no registrations in this session.</p>';
        }
        $html .= '</body></html>';
        */
        $html = 'Data preview disabled';

        header('Content-Type: text/html');
        print $html;

        // ------------------------------------
        // get root certs from FIDO Alliance Metadata Service
        // ------------------------------------

    } else if ($fn === 'queryFidoMetaDataService') {

        $mdsFolder = 'rootCertificates/mds';
        $success = false;
        $msg = null;

        // fetch only 1x / 24h
        $lastFetch = \is_file($mdsFolder .  '/lastMdsFetch.txt') ? \strtotime(\file_get_contents($mdsFolder .  '/lastMdsFetch.txt')) : 0;
        if ($lastFetch + (3600 * 48) < \time()) {
            $cnt = $WebAuthn->queryFidoMetaDataService($mdsFolder);
            $success = true;
            \file_put_contents($mdsFolder .  '/lastMdsFetch.txt', date('r'));
            $msg = 'successfully queried FIDO Alliance Metadata Service - ' . $cnt . ' certificates downloaded.';
        } else {
            $msg = 'Fail: last fetch was at ' . date('r', $lastFetch) . ' - fetch only 1x every 48h';
        }

        $return = new stdClass();
        $return->success = $success;
        $return->msg = $msg;

        header('Content-Type: application/json');
        print(json_encode($return));
    }
} catch (Throwable $ex) {
    $return = new stdClass();
    $return->success = false;
    $return->msg = $ex->getMessage();

    header('Content-Type: application/json');
    print(json_encode($return));
}
