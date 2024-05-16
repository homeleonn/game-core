<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$user->login?> - информация о персонаже</title>
    <link rel="stylesheet" href="/style/bootstrap-grid.my.min.css">
    <style>
        body {
            background: #eee;
        }

        .user-info {
            padding: 10px;
        }

        .user-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-info tr:first-child,
        .chars table tr:nth-child(2) {
            text-align: center;
            font-weight: bold;
        }

        .user-info td:first-child {
            width: 100%;
        }

        .user-info td {
            padding: 3px 5px;
        }

        .user-info table,
        .user-info td {
            border: 2px #ccc solid;
        }

        .user-info .user-form {
            text-align: center;
        }

        .row.col {
            flex-direction: column;
        }

        .uf-slot {
            width: 60px;
            height: 60px;
        }

        .uf-slots {
            width: 60px;
        }

        .wrap {
            /*display: inline-block;*/
            /*margin: 0 auto;*/
            position: relative;
            left: 50%;
            margin-left: -110px;
            margin-top: 10px;
        }

        .user-form .wrap > .row {
            float: left;
            margin: 0;
            padding: 0;
        }

        .uf-avatar {
            /*width: 100px;*/
        }

    </style>
</head>
<body>
<div class="user-info">
    <div class="row">
        <div class="col-md-4 chars">
            <table>
                <tr>
                    <td colspan=3>Характеристики</td>
                </tr>
                <tr>
                    <td>Имя</td>
                    <td>База</td>
                    <td>Доп</td>
                </tr>
                <?php foreach (['Сила' => 'power', 'Интуиция' => 'critical', 'Ловкость' => 'evasion', 'Выносливость' => 'stamina', ] as $key => $userKey): ?>
                <tr>
                    <td><?=$key?></td>
                    <td><?=$user->{$userKey}?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div class="col-md-4  user-form">
            <?php if ($user->tendency_img): ?>
                <img
                    src="/img/tendencies/<?=$user->tendency_img?>"
                    alt="<?=$user->tendency_name?>"
                    title="<?=$user->tendency_name?>"
                >
            <?php endif; ?>
            <?php if ($user->clan_img): ?>
                <img
                    src="/img/clans/<?=$user->clan_img?>"
                    alt="<?=$user->clan_name?>"
                    title="<?=$user->clan_name?>"
                >
            <?php endif; ?>
            <?=$user->login?>
            [<?=$user->level?>]
            <div class="wrap">
                <div class="row col uf-slots">
                <?php foreach (['head', 'rhand', 'chest'] as $bodypart): ?>
                    <div class="uf-slot">
                        <img src="/img/<?=isset($items[$bodypart])
                                ? "items/{$items[$bodypart]->image}"
                                : "slots/{$bodypart}.png"?>"
                        >
                    </div>
                <?php endforeach; ?>
                </div>
                <div class="row uf-avatar">
                    <img src="/img/images/<?=$user->sex?>.png">
                </div>

                <div class="row col uf-slots">
                <?php foreach (['gloves', 'lhand', 'legs', 'feet'] as $bodypart): ?>
                    <div class="uf-slot">
                        <img src="/img/<?=isset($items[$bodypart])
                                ? "items/{$items[$bodypart]->image}"
                                : "slots/{$bodypart}.png"?>"
                        >
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <table>
                <tr>
                    <td colspan="2">Статистика</td>
                </tr>
                <?php foreach (['Побед' => 'win', 'Поражений' => 'defeat', 'Ничьих' => 'draw', 'Клан' => 'clan_name', ] as $key => $userKey): ?>
                <tr>
                    <td><?=$key?></td>
                    <td><?=$user->{$userKey}?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
