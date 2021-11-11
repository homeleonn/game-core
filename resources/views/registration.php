<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация в игре FightWorld</title>
    <link rel="stylesheet" href="css/bootstrap-grid.min.css">
    <link rel="stylesheet/less" href="css/style.less">
</head>
<body class="reg">
    <h1 class="center">Регистрация</h1>
    <?php if (!empty($errors)): foreach ($errors as $e): ?>
    <div class="errors"><?=$e?></div>
    <?php endforeach; endif; ?>
    <form class="form" method="post" action="registration">
        <?=csrf_field()?>
        <table>
            <tr>
                <td>E-mail:</td>
                <td><input type="text" name="email" value="<?=old('email')?>"></td>
            </tr>
            <tr>
                <td>Логин:</td>
                <td><input type="text" name="login" value="<?=old('login')?>"></td>
            </tr>
            <tr>
                <td>Пароль:</td>
                <td><input type="password" name="password"></td>
            </tr>
            <tr>
                <td><img src="/captcha" alt=""></td>
                <td><input type="text" name="captcha" placeholder="type the captcha..."></td>
            </tr>
            <tr>
                <td colspan="2">
                    <button>Зарегистрироваться</button>
                </td>
            </tr>
        </table>
        <div></div>
    </form>

    <script src="js/less.min.js"></script>
</body>
</html>
