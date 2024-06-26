<?php

namespace Homeleon\Auth;

use Homeleon\Contracts\Database\Database;
use Homeleon\Contracts\Session\Session;

class Auth
{
    private string $auth = 'email';
    private string $passwordKey = 'password';

    public function __construct(
        private readonly Database $db,
        private readonly Session  $session,
    ) {}

    public function check()
    {
        return $this->session->get('id');
    }

    public function login($user): void
    {
        $this->session->set('id', $user->id);
    }

    public function attempt(array $data): bool
    {
        if (!isset($data[$this->auth]) || !isset($data[$this->passwordKey])) {
            return false;
        }

        $user = $this->db->getRow('SELECT id, password FROM users WHERE email = ?s', $data[$this->auth]);

        if (!$user || !$this->passwordVerify($data[$this->passwordKey], $user->password)) {
            return false;
        }

        $this->session->set('id', $user->id);

        return true;
    }

    private function passwordVerify(string $password, string $actualPassword): bool
    {
        return password_verify($password, $actualPassword);
    }

    public function logout(): void
    {
        $this->session->del('id');
    }
}
