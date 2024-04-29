<?php

namespace App\Http\Controllers;

use Homeleon\Http\Request;
use Homeleon\Support\Facades\Auth;
use Homeleon\Support\Facades\Config;
use Homeleon\Support\Facades\DB;
use Homeleon\Captcha\Captcha;
use App\Server\Models\User;
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

    public function wsToken(Request $request)
    {
        // dd(s());
        if (isProd()) {
            if (!s('id')) return;
            usleep(500000);
            echo generateToken((int)s('id'));
        } else {
            echo generateToken((int)($request->get('id') ?? 1));
        }
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

    public function forcedLogin(Request $request, Captcha $captcha)
    {
        $recaptcha = new ReCaptcha('6LfbKEUpAAAAAHlYlp7P89MFkn6WKylbBzr-7oIJ');
        $resp = $recaptcha->setExpectedHostname($_SERVER["SERVER_NAME"])
                  ->verify($request->get('g-recaptcha-response'), $_SERVER["REMOTE_ADDR"]);
        if (!$resp->isSuccess()) {
            $errors = $resp->getErrorCodes();
            return redirect()->route('entry')->with('error', join("<br>", $errors));
        }
        // if (!$captcha->isValid()) return redirect()->route('entry')->with('error', 'Неверный ввод проверочного слова');

        User_::create();

        return redirect()->route('main');
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

    public function testForm(Request $request)
    {
    }

    public function test1(int $userId = 1)
    {
        dd(route('test', [22]));
        dd($userId);
    }

    public function test2(int $userId = 1, $a = 2)
    {
        dd(route('test', [443]));
    }
}
