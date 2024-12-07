<?php

class Crypt
{
    private $iv; // Initialization vector
    private $cipher; // Cipher algorithm

    public function __construct()
    {
        $this->iv = random_bytes(16); // Generate a random initialization vector
        $this->cipher = "AES-256-CBC"; // Algorithm used for encryption/decryption
    }

    public function encryptData($data)
    {
        // Encrypt data
        $encryptedString = openssl_encrypt($data, $this->cipher, SECRET_KEY, OPENSSL_RAW_DATA, $this->iv);
        $encryptedBase64 = base64_encode($this->iv . $encryptedString);
        return json_encode(array("data" => $encryptedBase64));
    }

    public function decryptData($encryptedString)
    {
        $data = $encryptedString['data'];
        $decoded = base64_decode($data);
        $iv_decoded = substr($decoded, 0, 16); // Extract IV from the beginning
        $encrypted_data = substr($decoded, 16); // Extract encrypted data
        $decrypted = openssl_decrypt($encrypted_data, $this->cipher, SECRET_KEY, OPENSSL_RAW_DATA, $iv_decoded);
        return $decrypted;
    }
}

?>
