<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Server\Models\Item;
use Homeleon\Support\Common;

class UserController extends Controller
{
    public function info($userId)
    {
        $user = \DB::getRow('SELECT
            u.id, u.login, u.level, u.power, u.critical, u.evasion, u.stamina, u.win, u.defeat, u.draw, u.rhand, u.lhand, u.dblhand, u.fight, u.image, u.sex,
            c.name as clan_name, c.img as clan_img,
            t.name as tendency_name, t.img as tendency_img,
            l.name as location_name
                FROM users u
                    LEFT JOIN clans c ON u.clan = c.id
                    LEFT JOIN tendencies t ON c.id = t.id
                    LEFT JOIN locations l ON u.id = l.id
                where u.id = ?i LIMIT 1', $userId);

        $items = \DB::getAll('SELECT i.*, a.* from items i LEFT JOIN allitems a ON i.item_id = a.item_id WHERE i.owner_id = ' . $user->id . ' AND i.loc = "WEARING"');

        $items = Common::itemsOnKeys($items, ['body_part']);

        return view('user.info', compact('user', 'items'));
    }
}
