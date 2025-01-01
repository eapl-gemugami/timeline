const errorBox = document.querySelector(".error-box");

/**
 * Creates a new Webauthn/Passkey registration
 * @returns {undefined}
 */
async function createRegistration() {
    try {
        //errorBox.textContent = 'Calling createRegistration';

        // Check browser support
        if (!window.fetch || !navigator.credentials || !navigator.credentials.create) {
            throw new Error('Browser not supported.');
        }

        // Add this message
        // The request either timed out, was canceled or the device is already registered. Please try again or try using another device.

        // Show the Device Type as in this table: https://www.corbado.com/glossary/aaguid

        // Get create args
        let rep = await window.fetch('api?fn=getCreateArgs' + getURLParams(), {
            method: 'GET',
            cache: 'no-cache'
        });
        const createArgs = await rep.json();

        // Generic error handling
        if (createArgs.success === false) {
            throw new Error(createArgs.msg || 'Unknown error occured');
        }

        // Replace binary base64 data with ArrayBuffer.
        // Another way to do this is the reviver function of JSON.parse()
        recursiveBase64StrToArrayBuffer(createArgs);

        // Create credentials
        const cred = await navigator.credentials.create(createArgs);

        // Create object
        const authenticatorAttestationResponse = {
            transports: cred.response.getTransports ? cred.response.getTransports() : null,
            clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
            attestationObject: cred.response.attestationObject ? arrayBufferToBase64(cred.response.attestationObject) : null
        };

        // Check auth on server side
        rep = await window.fetch('api?fn=processCreate' + getURLParams(), {
            method: 'POST',
            body: JSON.stringify(authenticatorAttestationResponse),
            cache: 'no-cache'
        });
        const authenticatorAttestationServerResponse = await rep.json();

        // Prompt server response
        if (authenticatorAttestationServerResponse.success) {
            //window.alert(authenticatorAttestationServerResponse.msg || 'registration success');
            errorBox.textContent = authenticatorAttestationServerResponse.msg || 'registration success';
        } else {
            throw new Error(authenticatorAttestationServerResponse.msg);
        }

    } catch (err) {
        errorBox.textContent = err;
        //window.alert(err.message || 'unknown error occured');
    }
}

/**
 * Checks a FIDO2 registration
 * @returns {undefined}
 */
async function checkRegistration() {
    try {
        if (!window.fetch || !navigator.credentials || !navigator.credentials.create) {
            throw new Error('Browser not supported.');
        }

        // Get Check args
        let rep = await window.fetch('api?fn=getCredentialsGetArgs' + getURLParams(), {
            method: 'GET',
            cache: 'no-cache'
        });
        const getArgs = await rep.json();

        // Error handling
        if (getArgs.success === false) {
            throw new Error(getArgs.msg);
        }

        // Replace binary base64 data with ArrayBuffer.
        // Another way to do this is the reviver function of JSON.parse()
        recursiveBase64StrToArrayBuffer(getArgs);

        // Check credentials with device
        const cred = await navigator.credentials.get(getArgs);

        // Create object to transmit to server
        const authenticatorAttestationResponse = {
            id: cred.rawId ? arrayBufferToBase64(cred.rawId) : null,
            clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
            authenticatorData: cred.response.authenticatorData ? arrayBufferToBase64(cred.response.authenticatorData) : null,
            signature: cred.response.signature ? arrayBufferToBase64(cred.response.signature) : null,
            userHandle: cred.response.userHandle ? arrayBufferToBase64(cred.response.userHandle) : null
        };

        // Send to server
        rep = await window.fetch('api?fn=processCredentialsGet' + getURLParams(), {
            method: 'POST',
            body: JSON.stringify(authenticatorAttestationResponse),
            cache: 'no-cache'
        });
        const authenticatorAttestationServerResponse = await rep.json();

        // Check server response
        if (authenticatorAttestationServerResponse.success) {
            //errorBox.textContent = authenticatorAttestationServerResponse.msg || 'Login success';
            //window.alert(authenticatorAttestationServerResponse.msg || 'login success');
            return true;
        } else {
            throw new Error(authenticatorAttestationServerResponse.msg);
        }

    } catch (err) {
        errorBox.textContent = err;
        //window.alert(err.message || 'unknown error occured');
    }
}

/**
 * convert RFC 1342-like base64 strings to array buffer
 * @param {mixed} obj
 * @returns {undefined}
 */
function recursiveBase64StrToArrayBuffer(obj) {
    let prefix = '=?BINARY?B?';
    let suffix = '?=';
    if (typeof obj === 'object') {
        for (let key in obj) {
            if (typeof obj[key] === 'string') {
                let str = obj[key];
                if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
                    str = str.substring(prefix.length, str.length - suffix.length);

                    let binary_string = window.atob(str);
                    let len = binary_string.length;
                    let bytes = new Uint8Array(len);
                    for (let i = 0; i < len; i++) {
                        bytes[i] = binary_string.charCodeAt(i);
                    }
                    obj[key] = bytes.buffer;
                }
            } else {
                recursiveBase64StrToArrayBuffer(obj[key]);
            }
        }
    }
}

/**
 * Convert a ArrayBuffer to Base64
 * @param {ArrayBuffer} buffer
 * @returns {String}
 */
function arrayBufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

/**
 * Get URL parameter
 * @returns {String}
 */
function getURLParams() {
    let url = '';

    url += '&apple=1&yubico=1&solo=1&hypersecu=1&google=1&microsoft=1&mds=1';

    url += '&requireResidentKey=1';

    url += '&type_usb=1&type_nfc=1&type_ble=1&type_int=1&type_hybrid=1';

    url += '&fmt_none=1';
    url += '&fmt_android-key=0&fmt_android-safetynet=0&fmt_apple=0';
    url += '&fmt_fido-u2f=0&fmt_packed=0&fmt_tpm=0';
    //url += '&fmt_android-key=1&fmt_android-safetynet=1&fmt_apple=1';
    //url += '&fmt_fido-u2f=1&fmt_packed=1&fmt_tpm=1';

    url += '&rpId=' + location.hostname;

    url += '&userId=01';
    url += '&userName=admin';
    url += '&userDisplayName=Timeline Admin';

    //url += '&userVerification=discouraged';
    url += '&userVerification=required';

    return url;
}