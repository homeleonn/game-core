<?php

namespace App\Server\Models\Fight;

class EndFightHandler
{
    public function processFighter($fighter, $isWinner)
    {
        $fighter->user->fight = 0;

        return $fighter->isBot() ? $this->processBot($fighter, $isWinner) : $this->processUser($fighter, $isWinner);
    }

    public function processUser($fighter, $isWinner)
    {
        if ($isWinner) {
            $fighter->win += 1;
        } else {
            $fighter->defeat += 1;
        }

        $fighter->last_restore = time();
        // $fighter->sendChars(\App::make('game'), ['curhp', 'maxhp', 'win', 'defeat', 'fight']);
        $fighter->save();
    }

    public function processBot($fighter)
    {
        if ($fighter->curhp <= 0) {
            repo('npc')->kill($fighter);
        } else {
            $fighter->curhp = $fighter->maxhp;
            \App::make('game')->sendToLoc($fighter->loc_id, ['monsterGotFree' => $fighter->id]);
        }
    }
}
