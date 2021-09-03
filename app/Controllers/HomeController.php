<?php

namespace App\Controllers;

use Core\Facades\Request;
use Core\Facades\Auth;
use Core\Facades\Config;


class HomeController
{
    public function index()
    {
        return view('home', ['test' => __METHOD__]);
    }

    public function main()
    {
        // dd(s('loc') ?? '');
        return view('main');
    }

    public function wsToken()
    {
        // dd(s());
        // echo 1;
        if (Config::get('env') == 'prod') usleep(500000);
        // echo generateToken(s('id'));
        echo generateToken(1);
        // echo json_encode(['token' => generateToken(s('id'))]);
    }

    public function entry()
    {
        return view('entry');
    }

    public function logout()
    {
    	s('id', null);
		return redirect()->route('entry');
    }

    public function login()
    {
        $request = \App::make('request');

        if (Auth::attempt($request->only(['email', 'password']))) {
            return redirect()->route('main');
        }

        return redirect()->route('entry');
    }
}