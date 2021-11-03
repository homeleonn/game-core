<?php

namespace App\Server\Helpers;

use Homeleon\Support\Facades\App;

class HFight
{
    public static function checkAttack($u1, $u2, $type, $superHitLevel)
    {
        $crit = self::checkChance(self::damageCalculate($u1, $u2, 'critical', 'evasion', 'defence')[0]);
        $evasion = self::checkChance(self::damageCalculate($u1, $u2, 'evasion', 'critical', 'defence')[1]);
        $block = false;
        if (!$evasion) {
            $block = self::checkChance(self::damageCalculate($u1, $u2, 'defence', 'critical', 'evasion')[1]);
        }

        if ($superHitLevel && ($evasion || $block) && self::cancelDefenceBySuperHit($u1->level, $superHitLevel)) {
            $evasion = $block = false;
        }

        return [$crit, $evasion, $block, $superHitLevel];
    }

    private static function cancelDefenceBySuperHit($userLevel, $superHitLevel)
    {
        $chance = 0;
        $diffLevel = $userLevel - $superHitLevel;

        if ($diffLevel < 1) {
            return true;
        } else if ($diffLevel === 1) {
            $chance = 70;
        } else if ($diffLevel === 2) {
            $chance = 40;
        } else if ($diffLevel === 3) {
            $chance = 10;
        }

        return self::checkChance($chance);
    }


    // @check must be in range 0-100
    private static function checkChance($chance)
    {
        $multiplier = 10;
        $randNum = mt_rand(0, 100 * $multiplier);
        return $randNum < $chance * $multiplier;
    }

    private static function damageCalculate($u1, $u2, $stat1, $stat2, $stat3)
    {
        $r1 = 0;
        $r2 = 0;
        $min = 5;

        [$cr1, $cr2] = self::calcLevel($u1, $u2);
        $r1 += $cr1; $r2 += $cr2;

        [$cr1, $cr2] = self::calcStatBigger($u1, $u2, $stat1);
        $r1 += $cr1; $r2 += $cr2;

        [$r1, $r2] = array_map(function ($r) use ($min, $stat1, $stat2, $stat3) {
            $result = round($r[0] + self::calcStat($r[1], $stat1, $stat2, $stat3));
            return $result < $min ? $min : $result;
        }, [[$r1, $u1], [$r2, $u2]]);

        return [$r1, $r2];
    }

    private static function calcLevel($u1, $u2)
    {
        $r1 = 0;
        $r2 = 0;
        $max = 10;

        if ($u1->level === $u2->level) return [$r1, $r2];

        if ($u1->level > $u2->level) {
            $r1 += ($u1->level - $u2->level) * 2;
        } else if ($u1->level < $u2->level) {
            $r2 += ($u2->level - $u1->level) * 2;
        }

        foreach ([$r1, $r2] as &$r) {
            if ($r > $max) $r = $max;
        }

        return [$r1, $r2];
    }

    private static function calcStatBigger($u1, $u2, $stat)
    {
        $r1 = 0;
        $r2 = 0;
        $max = 15;
        $division = 15;

        if ($u1->{$stat} === $u2->{$stat}) return [$r1, $r2];

        if ($u1->{$stat} > $u2->{$stat}) {
            $r1 += ($u1->{$stat} - $u2->{$stat}) / $division;
            if ($r1 > $max) $r1 = $max;
            $r2 -= $r1;
        } else if ($u1->{$stat} < $u2->{$stat}) {
            $r2 += ($u2->{$stat} - $u1->{$stat}) / $division;
            if ($r2 > $max) $r2 = $max;
            $r1 -= $r2;
        }

        return [$r1, $r2];
    }

    private static function calcStat($u, $stat1, $stat2, $stat3)
    {
        $max = 30;
        $r = 0;
        $multi = 2;

        if ($u->{$stat1} < $u->{$stat2} || $u->{$stat1} < $u->{$stat3}) {
            $multi = 1;
        }

        $r = $u->{$stat1} / 5 * $multi;
        if ($r > $max) $r = $max;

        return $r;
    }

    public static function generateSuperHit($existingSuperHits, $level)
    {
        if (!$existingSuperHits) $existingSuperHits = [];
        $superHitSteps = [
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 4,
            5 => 5,
            6 => 5,
            7 => 6,
            8 => 6,
            9 => 7,
            10 => 7,
        ];

        $generatedSuperHit = [];
        $needSteps = $superHitSteps[$level];
        $step = 1;
        $duplicate = true;
        $requiredSteps = 0;

        do {
            for ($i = 0; $i < $needSteps; $i++) {
                $generatedSuperHit[$i] = mt_rand(1, 3);
            }

            $duplicate = false;
            $superHitLength = count($generatedSuperHit);

            foreach ($existingSuperHits as $hitLevel => $sh) {
                // Проверяем по кускам. Пример: [1, 2, 3] разбиваем по [1, 2] & [2, 3] и сверяем
                for ($chunkOffset = 0; $superHitSteps[$hitLevel] + $chunkOffset <= $superHitLength; $chunkOffset++) {
                    $checkSteps = array_slice($generatedSuperHit, $chunkOffset, $superHitSteps[$hitLevel]);

                    if (join('.', $checkSteps) == join('.', $existingSuperHits[$hitLevel]['hit'])) {
                        // d('duplicate: ' . json_encode($generatedSuperHit));
                        $duplicate = true;
                        break 2;
                    }
                }
            }

            if ($requiredSteps > 500) {
                d('Too many attempts for generating super hit!');
                return;
            }
            $requiredSteps++;

        } while ($duplicate);

        return $generatedSuperHit;
    }

    public static function send($messageType, ...$args)
    {
        $methodName = 'message' . ucfirst($messageType);
        self::$methodName(...$args);
    }

    public static function messageHit($curFighter, $type, $damage, $crit, $block, $evasion, $superHit)
    {
        // if ($damage == 0) return;
        $sendHitData = ['_fight' => [
            'hit' => [
                'hitter' => $curFighter->fId,
                'defender' => $curFighter->getEnemy()->fId,
                'damage' => $damage,
            ],
        ]];

        $sendPrivateHitData = $sendHitData;
        $privateProps = ['type', 'crit', 'block', 'evasion', 'superHit'];
        foreach ($privateProps as $prop) {
            $sendPrivateHitData['_fight']['hit'][$prop] = $$prop;
        }

        foreach ($curFighter->fight->fightersById as $fighter) {
            if (self::isCurrentFighter($fighter, $curFighter)) {
                $hitData = $sendPrivateHitData;
            } else {
                if ($damage == 0) continue;
                $hitData = $sendHitData;
            }

            App::make('game')->send($fighter->getFd(), $hitData);
        }
    }

    public static function isCurrentFighter($fighter, $curFighter)
    {
        return $fighter->fId == $curFighter->fId
                || $fighter->fId == $curFighter->enemyfId;
    }

    public static function messageSwap($fighter)
    {
        if (is_null($fighter->swap)) return;
        $pair = [$fighter, $fighter->enemyfId ? $fighter->getEnemy() : $fighter->fight->fighters[$fighter->lastEnemyfId]];
        foreach ($pair as $fighter) {
            // if (is_null($fighter->swap)) {
            //     continue;
            // }
            App::make('game')->send($fighter, ['_fight' => ['swap' => $fighter->swap]]);
        }
    }

    public static function messageStatistics($fighters, $startTime, $winTeam, $teamsStatisticts)
    {
        foreach ($fighters as $fighter) {
            App::make('game')->send($fighter, ['_fight' => ['statistics' => [
                'startTime' => $startTime,
                'winTeam' => $winTeam,
                'teamsStatisticts' => $teamsStatisticts,
            ]]]);
        }
    }

    public static function messageKill($fighters, $fId)
    {
        foreach ($fighters as $fighter) {
            App::make('game')->send($fighter, ['_fight' => ['kill' => [
                'fId' => $fId,
            ]]]);
        }
    }
}
