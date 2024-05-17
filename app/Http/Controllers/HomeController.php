<?php

namespace App\Http\Controllers;

use App\Services\JWT\JWT;
use Homeleon\Http\Request;
use Homeleon\Support\Facades\Auth;
use Homeleon\Support\Facades\Config;
use Homeleon\Support\Facades\DB;
use Homeleon\Captcha\Captcha;
use App\Models\User as User_;
use ReCaptcha\ReCaptcha;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', ['test' => __METHOD__]);
    }

    public function main()
    {
        return view('game');
    }

    public function entry(Request $request)
    {
        return view('entry');
    }

    public function logout()
    {
    	Auth::logout();

		return redirect()->route('entry');
    }

    public function login(Request $request)
    {
        if (Auth::attempt($request->only(['email', 'password']))) {
            return redirect()->route('main');
        }

        return redirect()->route('entry');
    }

    public function getCaptcha()
    {
        \App::make(Captcha::class)->set();
    }

    public function registrationForm()
    {
        return view('registration');
    }

    public function registration(Request $request)
    {
        return redirect()->route('registration');
        // $this->validate($request->all(), [
        //     'email' => 'required|email',
        //     'login' => 'required|minlen:3',
        //     'password' => 'required|minlen:6',
        // ]);

        // if (DB::table('users')->where('email', $request->get('email'))->first()) {
        //     return redirect()->back()->with('error', 'Данный email уже занят');
        // }

        // dd($request->all());
    }

    public function test1(Request $request, int $userId = 1)
    {
        return ['request' => $request->all()];
    }
}
