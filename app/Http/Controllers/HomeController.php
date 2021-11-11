<?php

namespace App\Http\Controllers;

use Homeleon\Http\Request;
use Homeleon\Support\Facades\Auth;
use Homeleon\Support\Facades\Config;
use Homeleon\Support\Facades\DB;
use App\Server\Models\User;

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

    public function getCaptcha()
    {
        \App::make(\Homeleon\Captcha\Captcha::class)->set();
    }

    public function registrationForm()
    {
        return view('registration');
    }

    public function registration(Request $request)
    {
        $this->validate($request->all(), [
            'email' => 'required|email',
            'login' => 'required|minlen:3',
            'password' => 'required|minlen:6',
        ]);

        if (DB::table('users')->where('email', $request->get('email'))->first()) {
            return redirect()->back()->with('error', 'Данный email уже занят');
        }

        dd($request->all());
    }

    public function test(Request $request)
    {

        // return view('test');
        // dd($request, $userId);
    }

    public function testForm(Request $request)
    {

        // DB::table('user_quests')
        //           ->where('user_id', 1)
        //           ->andWhere('quest_id', 1)
        //           ->update(['completed' => 1]);

        // dd(DB::table('items')->whereIn('item_id', [1,2,3])->delete());
        // dd(DB::table('users')->count()->first());
            // dd(DB::table('quests')
            //   ->where('npc_id', 6)
            //   ->whereIn('level', [1,2,3])
            //   ->limit(33)
            //   ->all());

        // echo 1;
        // DB::table('tendencies')->insert([
        //     ['name' => 'qwe', 'img' => 'img1'],
        //     ['name' => 'qwe111', 'img' => 'img1111'],
        // ]);

        // dd(DB::getAll('SELECT n.*, s.* FROM npc n LEFT JOIN spawnlist s ON n.id = s.npc_id'));
        // echo 1;
        // User::find(1)->update(['fight' => 0]);
        // User::find(1)->update(['level' => 4]);
        // DB::query('UPDATE users SET curhp = 18, maxhp = 18, power = 5, critical = 5 where id = 1');
        // $time = time();
        // DB::query("UPDATE users SET curhp = 18, last_restore = {$time} where id = 1");
        // DB::query('UPDATE items SET loc = "INVENTORY"');
        // d(\Session::all());
        // return view('test');
        // dd($request, $userId);
        // dd(\DB::getAll('SELECT * FROM spawnlist'));
        // dd(\App\Server\Models\Npc::all());
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
