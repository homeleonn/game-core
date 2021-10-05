<?php

namespace App\Controllers;

use Core\Support\Facades\Request;
use Core\Support\Facades\Auth;
use Core\Support\Facades\Config;


class HomeController
{
    public function index()
    {
        return view('home', ['test' => __METHOD__]);
    }

    public function main()
    {
        return view('main');
    }

    public function wsToken()
    {
        if (Config::get('env') == 'prod') usleep(500000);
        echo generateToken(1);
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

    public function test()
    {
        // dd(\Core\Helpers\Common::propsOnly((object)['a' => 1, 'b' => 2, 'c' => 3], ['b', 'c']));
        // $users = \DB::getAll('Select * from users');
        // return view('test', compact('users'));
    }
}