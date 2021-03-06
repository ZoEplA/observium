<?php

require_once __DIR__ . '/bootstrap.php';

// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'phpseclib\\crypt\\aes' => '/Crypt/AES.php',
                'phpseclib\\crypt\\base' => '/Crypt/Base.php',
                'phpseclib\\crypt\\blowfish' => '/Crypt/Blowfish.php',
                'phpseclib\\crypt\\des' => '/Crypt/DES.php',
                'phpseclib\\crypt\\hash' => '/Crypt/Hash.php',
                'phpseclib\\crypt\\random' => '/Crypt/Random.php',
                'phpseclib\\crypt\\rc2' => '/Crypt/RC2.php',
                'phpseclib\\crypt\\rc4' => '/Crypt/RC4.php',
                'phpseclib\\crypt\\rijndael' => '/Crypt/Rijndael.php',
                'phpseclib\\crypt\\rsa' => '/Crypt/RSA.php',
                'phpseclib\\crypt\\ecdsa' => '/Crypt/ECDSA.php',
                'phpseclib\\crypt\\tripledes' => '/Crypt/TripleDES.php',
                'phpseclib\\crypt\\twofish' => '/Crypt/Twofish.php',
                'phpseclib\\file\\ansi' => '/File/ANSI.php',
                'phpseclib\\file\\asn1' => '/File/ASN1.php',
                'phpseclib\\file\\asn1\\element' => '/File/ASN1/Element.php',
                'phpseclib\\file\\x509' => '/File/X509.php',
                'phpseclib\\math\\biginteger' => '/Math/BigInteger.php',
                'phpseclib\\net\\scp' => '/Net/SCP.php',
                'phpseclib\\net\\sftp' => '/Net/SFTP.php',
                'phpseclib\\net\\sftp\\stream' => '/Net/SFTP/Stream.php',
                'phpseclib\\net\\ssh1' => '/Net/SSH1.php',
                'phpseclib\\net\\ssh2' => '/Net/SSH2.php',
                'phpseclib\\system\\ssh\\agent' => '/System/SSH/Agent.php',
                'phpseclib\\system\\ssh\\agent\\identity' => '/System/SSH/Agent/Identity.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    },
    true,
    false
);
// @codeCoverageIgnoreEnd
