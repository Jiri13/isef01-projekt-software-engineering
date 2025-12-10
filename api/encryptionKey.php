<?php
// Ein zufälliger, geheimer Schlüssel.
// In einer echten Produktion sollte dieser in einer Umgebungsvariable (.env) liegen.
define('CHAT_ENCRYPTION_KEY', 'phfAkq%NvN-Z2y+5S,<D§Q>Ha-<_jnLLq?tPThN{');
define('CHAT_CIPHER_METHOD', 'aes-256-cbc');

function encryptMessage($message) {
    $key = hash('sha256', CHAT_ENCRYPTION_KEY);

    // Initialisierungsvektor (IV) erzeugen - wichtig für Sicherheit
    $ivLength = openssl_cipher_iv_length(CHAT_CIPHER_METHOD);
    $iv = openssl_random_pseudo_bytes($ivLength);

    // Verschlüsseln
    $encrypted = openssl_encrypt($message, CHAT_CIPHER_METHOD, $key, 0, $iv);

    // Wir speichern IV und verschlüsselte Nachricht zusammen, getrennt durch "::"
    // Base64 encoding verhindert Probleme mit Sonderzeichen in der DB
    return base64_encode($encrypted . '::' . $iv);
}

function decryptMessage($encryptedString) {
    $key = hash('sha256', CHAT_ENCRYPTION_KEY);

    // Base64 zurückwandeln
    $data = base64_decode($encryptedString);

    // Prüfen ob das Format stimmt (Trennung durch ::)
    if (strpos($data, '::') === false) {
        return '[Nachricht kann nicht entschlüsselt werden]';
    }

    list($encrypted_data, $iv) = explode('::', $data, 2);

    return openssl_decrypt($encrypted_data, CHAT_CIPHER_METHOD, $key, 0, $iv);
}
?>