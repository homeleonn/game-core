<?php

namespace App\Http\Controllers;

use Homeleon\Http\Request;
use Homeleon\Support\Facades\Auth;
use Homeleon\Support\Facades\Config;
use App\Models\User;
use Homeleon\DB\DB;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', ['test' => __METHOD__]);
    }

    public function main()
    {
        return view('main');
    }

    public function wsToken(Request $request)
    {
        // dd($request);
        if (Config::get('env') == 'prod') usleep(500000);
        // d(Request::get('id'));
        echo generateToken((int)($request->get('id') ?? 1));
        exit;
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

    public function login(Request $request)
    {
        if (Auth::attempt($request->only(['email', 'password']))) {
            return redirect()->route('main');
        }

        return redirect()->route('entry');
    }

    // public function test(int $userId = 1)
    // {
    //     dd($userId);
    // }

    // public function test(User $user = null)
    // {
    //     // if ($user)
    //     // dd($user->login, $user);
    //     return view('test');
    // }

    public function registration(Request $request)
    {
        $this->validate($request->all(), [
            '_token' => 'required|minlen:20',
            'age' => 'required|integer',
        ]);
        // $request->validate([
        //     '_token' => 'required|minlen:20',
        //     'age' => 'required|integer',
        // ]);
        // d($request->all());
        // echo 22;
    }

    public function test(Request $request)
    {

        // return view('test');
        // dd($request, $userId);
    }

    public function testForm(Request $request)
    {
        d(\Session::all());
        return view('test');
        // dd($request, $userId);
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
