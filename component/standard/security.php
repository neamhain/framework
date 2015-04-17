<?php
    /**
     * Library Security
     * Processor of cryptography and security
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    function Random() {
        $_FilePointer = fopen('/dev/urandom', 'r');
        $_Random = fread($_FilePointer, 32);
        
        fclose($_FilePointer);
        
        return unpack('H*', $_Random)[1];
    }

    function Sha256($_Plain) {
        return hash('sha256', $_Plain . FRAMEWORK_HASH_SALT);
    }

    function Sha512($_Plain) {
        return hash('sha512', $_Plain . FRAMEWORK_HASH_SALT);
    }
    
    function Password($_Plain) {
        return password_hash($_Plain, PASSWORD_DEFAULT);
    }

    function AesEncrypt($_Plain, $_Key = FRAMEWORK_AES_KEY, $_IV = FRAMEWORK_AES_IV) {
        $_FillingSize = 16 - (strlen($_Plain) % 16);
        $_FilledData = $_Plain . str_repeat(chr($_FillingSize), $_FillingSize);
        $_Cipher = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $_Key, $_FilledData, MCRYPT_MODE_CBC, $_IV);
        
        return base64_encode($_Cipher);
    }
    
    function AesDecrypt($_Cipher, $_Key = FRAMEWORK_AES_KEY, $_IV = FRAMEWORK_AES_IV) {
        $_FilledData = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $_Key, base64_decode($_Cipher), MCRYPT_MODE_CBC, $_IV);
        $_DataSize = strlen($_FilledData);
        $_FillingSize = ord($_FilledData{$_DataSize - 1});
        
        return substr($_FilledData, 0, $_DataSize - $_FillingSize);
    }
    
    function CsrfTokenVerify($_CsrfToken) {
        return $_SESSION[Framework::Take('UniqueId')]['__CSRF_TOKEN__'] === $_CsrfToken;
    }
?>