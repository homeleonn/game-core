<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FightWorld - MMORPG browser game</title>
    <link rel="stylesheet" href="login_/css/bootstrap-grid.min.css">
    <link rel="stylesheet" type="text/css" href="login_/css/style.css">
    <link rel="stylesheet" type="text/css" href="login_/css/shower.css">
</head>
<body>
    <header></header>
    <main class="content">
        <?php
            // print_r(s());
        ?>
        <form action="<?=route('forced-login')?>" method="POST" id="login">
            <h2>Вход</h2>
            <?php if (s('error')):?><div class="red"><?=s('error')?></div><?php endif;?>
            <div class="captcha none">
                <hr>
                <img src="" class="captcha-image">
                <br>
                <input type="text" autocomplete="off" name="captcha_code" placeholder="Введите проверочный код сюда...">
            </div>
            <a class="btn center entry-button" href="#">Войти</a>
            <div class="inactive">
                <div>Email</div>
                <div><input type="text" name="email"></div>
                <div>Пароль</div>
                <div><input type="password" name="password"></div>
                <div><button>Войти</button></div>
                <div class="row">
                    <div class="col-md-6">
                        <label for="remember_me">Запомнить меня</label>
                        <input type="checkbox" id="remember_me" name="remember_me">
                    </div>
                    <div class="col-md-6">
                        <a href="#">Забыли пароль?</a>
                    </div>
                </div>
                <div><a href="registration" class="btn">Регистрация</a></div>
                <?=csrf_field();?>
            </div>
        </form>
        <div class="progressbar-circle2-wrapper">
            <defs>
                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="10%" stop-color="#ffff00"></stop>
                  <stop offset="100%" stop-color="#00ff00"></stop>
                </linearGradient>
              </defs>
            <svg class="progressbar-circle2">
                <circle class="back" cx="120" cy="120" r="110" />
                <circle class="first" cx="120" cy="120" r="110" />
                <circle class="second" cx="120" cy="120" r="110" />
            </svg>
            <div class="serverName"></div>
        </div>
    </main>


        <div class="container screens">
            <h2 class="center title-custom">
                Скриншоты из игры
            </h2>
            <small class="center block white">(На случай, если вдруг сервер "лежит" =)</small>
            <div class="row flex">
                <div class="col-md-4">
                    <a class="shower" href="login_/img/fightworld-screens/1.png">
                        <img src="login_/img/fightworld-screens/resize/1.png">
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="shower" href="login_/img/fightworld-screens/2.png">
                        <img src="login_/img/fightworld-screens/resize/2.png">
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="shower" href="login_/img/fightworld-screens/3.png">
                        <img src="login_/img/fightworld-screens/resize/3.png">
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="shower" href="login_/img/fightworld-screens/4.png">
                        <img src="login_/img/fightworld-screens/resize/4.png">
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="shower" href="login_/img/fightworld-screens/5.png">
                        <img src="login_/img/fightworld-screens/resize/5.png">
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="shower" href="login_/img/fightworld-screens/6.png">
                        <img src="login_/img/fightworld-screens/resize/6.png">
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="shower" href="login_/img/fightworld-screens/7.png">
                        <img src="login_/img/fightworld-screens/resize/7.png">
                    </a>
                </div>
            </div>
        </div>
    <footer></footer>

    <!-- <script src="login_/js/common.js"></script> -->
    <script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js'></script>
    <!-- <script>let $ = _;</script> -->
    <script src="login_/js/index.js"></script>
    <script src="login_/js/shower.js"></script>
</body>
</html>
