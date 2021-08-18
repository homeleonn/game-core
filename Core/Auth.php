<?php

namespace Core;

use Core\Facades\DB;

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
		[$user] = DB::getAll('Select id, password, login, location, transition_time_left from users where email = ?s', $data[$this->auth]);

		// dd($user, password_verify($data['password'], $user->password));
		if (!$user || !password_verify($data['password'], $user->password)) return false;

		s('id', $user->id);
		s('name', $user->login);
		s('room', $user->location);
		s('transitionTimeout', $user->transition_time_left);

		return true;
	}
}