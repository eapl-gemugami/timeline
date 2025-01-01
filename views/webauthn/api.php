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
 *      window.fetch ------------------>      getCredentialsGetArgs
 *                                                |
 *   navigator.credentials.get   <----------------'
 *           |
 *           '------------------------->      processCredentialsGet
 *                                                |
 *         alert ok or fail      <----------------'
 *
 * ------------------------------------------------------------
 */

# TODO: 2024-12
# [x] Save keys into a file
# [x] Disable showing sensitive debug info
# [x] Check that a user already registered can't register again
# [ ] Save registration date

require_once 'libs/WebAuthn-2.2.0/WebAuthn.php';
require_once 'libs/WebAuthn-2.2.0/JsonManager.php';

const RP_NAME = 'Timeline'; # Add site name or domain
const USER_ID = '01'; # For a single user instance we use a constant one
const TIMEOUT_SECS = 30;

try {
    session_start();

    // Read get argument and post body
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

        // Types selected on front end
        $typeUsb = !!filter_input(INPUT_GET, 'type_usb');
        $typeNfc = !!filter_input(INPUT_GET, 'type_nfc');
        $typeBle = !!filter_input(INPUT_GET, 'type_ble');
        $typeInt = !!filter_input(INPUT_GET, 'type_int');
        $typeHyb = !!filter_input(INPUT_GET, 'type_hybrid');

        // Cross-platform: true, if type internal is not allowed
        //                 false, if only internal is allowed
        //                 null, if internal and cross-platform is allowed
        $crossPlatformAttachment = null;
        if (($typeUsb || $typeNfc || $typeBle || $typeHyb) && !$typeInt) {
            $crossPlatformAttachment = true;
        } else if (!$typeUsb && !$typeNfc && !$typeBle && !$typeHyb && $typeInt) {
            $crossPlatformAttachment = false;
        }

        // New Instance of the server library.
        // make sure that $rpId is the domain name.
        $WebAuthn = new lbuchs\WebAuthn\WebAuthn(RP_NAME, $rpId, $formats);
    }

    // ------------------------------------
    // Request for create arguments - getCreateArgs
    // ------------------------------------
    if ($fn === 'getCreateArgs') {
        # Exclude credential IDs for that user
        $excludeCredentialIds = [];

        $jsonContent = loadJsonFromFile(FILE_PATH);
        foreach ($jsonContent as $reg) {
            if ($reg['userId'] === $userId) {
                $excludeCredentialIds[] = base64_decode($reg['credentialId']);
            }
        }

        $createArgs = $WebAuthn->getCreateArgs(\hex2bin($userId), $userName, $userDisplayName, TIMEOUT_SECS, $requireResidentKey, $userVerification, $crossPlatformAttachment, $excludeCredentialIds);

        header('Content-Type: application/json');

        print(json_encode($createArgs));

        # Save challenge to session. You have to deliver it to processGet later.
        $_SESSION['challenge'] = $WebAuthn->getChallenge();

        // ------------------------------------
        // Request for get arguments
        // ------------------------------------
    } else if ($fn === 'getCredentialsGetArgs') {
        $ids = [];

        if (!$requireResidentKey) {
            $registrations = loadJsonFromFile(FILE_PATH);
            foreach ($registrations as $reg) {
                if ($reg['userId'] === USER_ID) {
                    $ids[] = $reg['credentialId'];
                }
            }
        }

        $getArgs = $WebAuthn->getGetArgs($ids, TIMEOUT_SECS, $typeUsb, $typeNfc, $typeBle, $typeHyb, $typeInt, $userVerification);

        header('Content-Type: application/json');
        print(json_encode($getArgs));

        # Save challenge to session. You have to deliver it to processGet later.
        $_SESSION['challenge'] = $WebAuthn->getChallenge();

        // ------------------------------------
        // processCreate
        // ------------------------------------
    } else if ($fn === 'processCreate') {
        $clientDataJSON = base64_decode($post->clientDataJSON);
        $attestationObject = base64_decode($post->attestationObject);
        $challenge = $_SESSION['challenge'];

        // processCreate returns data to be stored for future logins.
        $data = $WebAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, $userVerification === 'required', true, false);

        // Add user info
        $data->userId = $userId;
        $data->userName = $userName;
        $data->userDisplayName = $userDisplayName;

        $data->credentialId = base64_encode($data->credentialId);
        $data->AAGUID = base64_encode($data->AAGUID);

        $jsonData = loadJsonFromFile(FILE_PATH);
        $jsonData[] = $data;
        saveJsonToFile(FILE_PATH, $jsonData);

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
        // processCredentialsGet
        // ------------------------------------
    } else if ($fn === 'processCredentialsGet') {
        $clientDataJSON = base64_decode($post->clientDataJSON);
        $authenticatorData = base64_decode($post->authenticatorData);
        $signature = base64_decode($post->signature);
        $userHandle = base64_decode($post->userHandle);
        #$id = base64_decode($post->id);
        $challenge = $_SESSION['challenge'] ?? '';
        $credentialPublicKey = null;

        # Looking up correspondending public key of the credential id
        # you should also validate that only ids of the given user name
        # are taken for the login.
        $registrations = loadJsonFromFile(FILE_PATH);
        foreach ($registrations as $reg) {
            if ($reg['credentialId'] === $post->id) {
                $credentialPublicKey = $reg['credentialPublicKey'];
                break;
            }
        }

        if ($credentialPublicKey === null) {
            throw new Exception('Public Key for credential ID not found!');
        }

        # If we have resident key, we have to verify
        # that the userHandle is the provided userId at registration
        if ($requireResidentKey && $userHandle !== hex2bin($reg['userId'])) {
            throw new \Exception('userId doesnt match (is '
                . bin2hex($userHandle) . ' but expect ' . $reg->userId . ')');
        }

        # Process the get request. throws WebAuthnException if it fails
        $WebAuthn->processGet($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey, $challenge, null, $userVerification === 'required');

        # Check if we can set the session cookie here
        session_start();
        $_SESSION['password'] = 'OK';

        $return = new stdClass();
        $return->success = true;

        header('Content-Type: application/json');
        print(json_encode($return));

        // ------------------------------------
        // Get root certs from FIDO Alliance Metadata Service - DEPRECATED
        // ------------------------------------
    } else if ($fn === 'queryFidoMetaDataService') {
        $mdsFolder = 'rootCertificates/mds';
        $success = false;
        $msg = null;

        // Fetch only 1x / 24h
        $lastFetch = \is_file( "$mdsFolder/lastMdsFetch.txt") ? \strtotime(\file_get_contents("$mdsFolder/lastMdsFetch.txt")) : 0;
        if ($lastFetch + (3600 * 48) < \time()) {
            $cnt = $WebAuthn->queryFidoMetaDataService($mdsFolder);
            $success = true;
            \file_put_contents( "$mdsFolder/lastMdsFetch.txt", date('r'));
            $msg = "successfully queried FIDO Alliance Metadata Service - $cnt certificates downloaded.";
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
