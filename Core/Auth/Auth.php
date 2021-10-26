<?php

namespace Core\Auth;

use Core\Contracts\Database\Database;
use Core\Contracts\Session\Session;

class Auth
{
    private $auth = 'email';

    public function __construct(
        private Database $db,
        private Session $storage,
    ) {}

    public function check()
    {
        return $this->storage->get('id');
    }

    public function attempt(array $data): bool
    {
        if (!isset($data[$this->auth]) || !isset($data['password'])) {
            return false;
        }

        $user = $this->db->getRow('SELECT id, password FROM users WHERE email = ?s', $data[$this->auth]);

        if (!$user || !$this->passwordVerify($data['password'], $user->password)) {
            return false;
        }

        $this->storage->set('id', $user->id);

        return true;
    }

    private function passwordVerify(string $password, string $actualPassword): bool
    {
        return password_verify($password, $actualPassword);
    }
}
