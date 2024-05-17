<?php

namespace App\Models;

use Homeleon\Support\Facades\DB;
use App\Server\Models\AppModel;
use App\Server\Helpers\HFight;

class User extends AppModel
{
    public static function create(): int
    {
        // dd(self::orderBy('id', 'DESC')->first()->id);
        $userId = (int)self::orderBy('id', 'DESC')->first()->id + 1;
        $user = new self;
        $user->login = 'Tester_' . $userId;
        $user->email = uniqid();
        $user->password = '123';
        $user->maxhp = 30;
        $user->sex = 0;
        $user->super_hits = HFight::generateSuperHit();
        $user->save();
        self::giveItems($user->id);
        \Auth::login($user);

        return $userId;
    }

    public static function giveItems($userId)
    {
        $item = [];
        foreach (range(20, 27) as $itemId) {
            $items[] = ['owner_id' => $userId, 'item_id' => $itemId];
        }

        DB::table('items')->insert($items);
        // DB::table('items')->insert([
        //     [
        //         'owner_id' => $user->id,
        //         'item_id' => 10,
        //         'count' => 3,
        //     ]
        // ]);
    }
}
