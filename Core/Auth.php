<?php

namespace Core;

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
		$users = json_decode(file_get_contents(resources('db.txt')));

		if (!isset($data[$this->auth]) || 
			!isset($data['password']) ||
			!isset($users->{$data[$this->auth]}) ||
			$users->{$data[$this->auth]}->password != $data['password']
		) return false;

		$user = $users->{$data[$this->auth]};

		s('id', $user->id);
		s('name', $user->login);
		s('room', $user->room);
		s('transitionTimeout', $user->transitionTimeout);

		return true;
	}
}