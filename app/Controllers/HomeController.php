<?php

namespace App\Controllers;

use Core\Facades\Request;
use Core\Facades\Auth;


class HomeController
{
    public function index()
    {
        return view('home', ['test' => __METHOD__]);
    }

    public function main()
    {
        // dd(s('room') ?? '');
        return view('main');
    }

    public function wsToken()
    {
        // dd(s());
        usleep(500000);
        echo generateToken(s('id'));
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