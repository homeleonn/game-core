<?php

namespace App\Models;

use Homeleon\Support\Facades\DB;
use App\Server\Models\AppModel;

class User extends AppModel
{
    public static function create()
    {
        // dd(self::orderBy('id', 'DESC')->first()->id);
        $user = new self;
        $user->login = 'Tester_' . ((int)self::orderBy('id', 'DESC')->first()->id + 1);
        $user->email = uniqid();
        $user->password = '123';
        $user->maxhp = 30;
        $user->save();
        DB::table('items')->insert([
            [
                'owner_id' => $user->id,
                'item_id' => 20,
            ],[
                'owner_id' => $user->id,
                'item_id' => 21,
            ],
        ]);
        \Auth::login($user);
        // dd($user);


        // [
        //         'owner_id' => $user->id,
        //         'item_id' => 22,
        //     ],[
        //         'owner_id' => $user->id,
        //         'item_id' => 23,
        //     ],[
        //         'owner_id' => $user->id,
        //         'item_id' => 24,
        //     ],[
        //         'owner_id' => $user->id,
        //         'item_id' => 25,
        //     ],[
        //         'owner_id' => $user->id,
        //         'item_id' => 26,
        //     ],[
        //         'owner_id' => $user->id,
        //         'item_id' => 27,
        //     ],
        // ]);

        // DB::table('items')->insert([
        //     [
        //         'owner_id' => $user->id,
        //         'item_id' => 10,
        //         'count' => 3,
        //     ],
        //     [
        //         'owner_id' => $user->id,
        //         'item_id' => 1,
        //         'count' => 1055,
        //     ],
        //     [
        //         'owner_id' => $user->id,
        //         'item_id' => 29,
        //         'count' => 8,
        //     ],
        // ]);
    }
}
