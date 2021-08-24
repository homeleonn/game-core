<?php

namespace Core;

use Core\Facades\DB;
use App\Client\Models\User;

class Auth
{
	private $auth = 'email';

	public function check()
	{
		// dd(1, session_id(), s('id'));
		return s('id');
	}

	public function attempt($data)
	{
		if (!isset($data[$this->auth]) || !isset($data['password'])) return false;
		// $users = json_decode(file_get_contents(resources('db.txt')));
		$user = DB::getRow('SELECT id, password FROM users WHERE email = ?s', $data[$this->auth]);
		// $user = User::where('email', $data[$this->auth])->select('id', 'password', 'login', 'location', 'transition_time_left')->first();

		// dd($user);

		// dd($user, password_verify($data['password'], $user->password));
		if (!$user || !password_verify($data['password'], $user->password)) return false;

		s('id', $user->id);
		// s('name', $user->login);
		// s('room', $user->location);
		// s('transitionTimeout', $user->transition_time_left);

		return true;
	}
}