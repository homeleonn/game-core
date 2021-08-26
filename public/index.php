<?php
header("Access-Control-Allow-Origin: *");
require '../vendor/autoload.php';
require '../functions.php';


// class Foo
// {
// 	public $a = 1;

// 	public function __construct(Closure $q = null)
// 	{
// 		$this->a = 2;
// 	}

// 	public function __get($key)
// 	{
// 		dd($key);
// 	}

// 	public function __set($key, $value)
// 	{
// 		dd($key);
// 	}

// 	public function z()
// 	{
// 		return $this->b;
// 	}

// }

// new Foo;

// exit;

// $q = new Foo; $qq = new Foo;

// array_map(function($item) {
// 	// d(++$item);
// 	$item->b = 3;
// }, [$q, $qq]);

// dd([$q, $qq]);

// // $foo = new Foo();
// // d($foo->z());

// $a = [new Foo(function(){}), new Foo];

// function b($a) {
// 	foreach ($a as $aa) $aa->w = 1;
// }

// d($a);
// b($a);
// dd($a);


// exit;

// echo json_encode(['a' => 1]);exit;

use Core\Session\Init as SessionInit;

new SessionInit($redis);

use Core\App;

try {
	$app = new App();
} catch (Exception $e) {
	// dd($e->getMessage());
}

require routes('web.php');

$app->run();