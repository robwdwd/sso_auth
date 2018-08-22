<?php

namespace SSOAuth;
class EncryptedSessionHandler extends \SessionHandler
{
    private $key;
    private $cipher;

    public function __construct($key, $cipher = 'aes-256-cbc')
    {

        if (mb_strlen($key, '8bit') !== 32) {
            throw new \Exception("Needs a 256-bit key (32 characters long)");
        }


        if (!in_array($cipher, openssl_get_cipher_methods())) {
            throw new \Exception("Cipher method is not supported by openssl.");
        }

        $this->cipher = $cipher;
        $this->key = $key;
    }

    public function read($id)
    {
        $data = parent::read($id);

        if (!$data) {
            return "";
        } else {

            $ivsize = openssl_cipher_iv_length($this->cipher);
            $iv = mb_substr($data, 0, $ivsize, '8bit');
            $ciphertext = mb_substr($data, $ivsize, null, '8bit');

            return openssl_decrypt(
                $ciphertext,
                $this->cipher,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );
        }
    }

    public function write($id, $data)
    {
        $ivsize = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivsize);

        if ($iv === FALSE) {
            throw new \Exception('OpenSSL failed to encrypt session data, no IV');
        }

        $ciphertext = openssl_encrypt(
            $data,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );


        if ($ciphertext === FALSE) {
            throw new \Exception('OpenSSL failed to encrypt session data.');
        }

        return parent::write($id, $iv.$ciphertext);
    }
}
