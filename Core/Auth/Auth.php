<?php

namespace Core\Auth;

use Core\Support\Facades\DB;
use App\Models\User;

class Auth
{
    private $auth = 'email';

    public function check()
    {
        return s('id');
    }

    public function attempt($data)
    {
        if (!isset($data[$this->auth]) || !isset($data['password'])) {
            return false;
        }

        $user = DB::getRow('SELECT id, password FROM users WHERE email = ?s', $data[$this->auth]);

        if (!$user || !password_verify($data['password'], $user->password)) {
            return false;
        }

        s('id', $user->id);

        return true;
    }
}
