<?php

namespace Homeleon\Support;

use RuntimeException;

class Crypter
{
    protected $method;
    protected $key;

    public function __construct($key, $method = 'AES-256-CBC')
    {
        $this->key    = $key;
        $this->method = $method;
    }

    public function encrypt($value)
    {
        $iv     = random_bytes(16);
        $value  = openssl_encrypt($value, $this->method, $this->key, 0, $iv);
        $json   = json_encode([
            'iv'    => base64_encode($iv),
            'value' => $value
        ]);

        return base64_encode($json);
    }

    public function decrypt($value)
    {
        $data = json_decode(base64_decode($value), true);

        if (!$this->validOptions($data)) {
            throw new RuntimeException('Wrong hash');
        }

        $iv = base64_decode($data['iv']);

        return openssl_decrypt($data['value'], $this->method, $this->key, 0, $iv);
    }

    protected function validOptions($options)
    {
        if (!is_array($options)) {
            return false;
        }

        return isset($options['iv'], $options['value']);
    }
}
