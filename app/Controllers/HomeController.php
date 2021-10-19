<?php

namespace App\Controllers;

// use Core\Support\Facades\Request;
use Core\Http\Request;
use Core\Support\Facades\Auth;
use Core\Support\Facades\Config;
use App\Models\User;


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
        // d(Request::get('id'));
        echo generateToken((int)(Request::get('id') ?? 1));
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

    // public function test(int $userId = 1)
    // {
    //     dd($userId);
    // }

    public function test(User $user)
    {
        dd($user->login, $user);
    }

    // public function test(Request $request, int $userId = 1)
    // {
    //     dd($request, $userId);
    // }
}
