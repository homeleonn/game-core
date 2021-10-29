<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hello world(Test)</title>
</head>
<body>
    <h1>Test</h1>
    <?php
    // dd(get_defined_vars());
        // var_dump($errors);
        // echo 1;
        // echo csrf_token();
    ?>
    <form method="post" action="/registration">
        token<br>
        <input type="text" name="_token" value="<?=csrf_token()?>">
        age<br>
        <input type="text" name="age" value="">
        <button>send</button>
    </form>
</body>
</html>
