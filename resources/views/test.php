<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hello world(Test)</title>
</head>
<body>
    <h1>Test</h1>
    <?php
        // echo 1;
        // echo csrf_token();
    ?>
    <form method="post" action="/registration">
        <input type="text" name="_token" value="<?=csrf_token()?>">
        <button>send</button>
    </form>
</body>
</html>
