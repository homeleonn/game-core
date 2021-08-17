<?php

$checkDir = __DIR__ . '/';

if (strpos(str_replace(__DIR__ . '/', '', $argv[1]), 'app') === false) {
	exit;
}

ob_start();
system('ps -aux | grep Fight');

$s = ob_get_contents();
ob_end_clean();

$process = preg_replace('/\s+/', ' ', explode(PHP_EOL, $s)[0]);
$processParts = '';

if (strpos($process, "FightWorld") !== false) {
	$processId = explode(' ', $process)[1];
	system('kill -9 ' . $processId);
}
start($checkDir);
// var_dump($argv, str_replace(__DIR__ . '/', '', $argv[1]));
function start($dir) {
	// system('php ' . $dir . 'app/app.php &');
	system('php ' . $dir . 'app/app.php > log.log &');
	// echo $argv[1];
	// var_dump($argv, __DIR__);
}

// var_dump($process, strpos($process, "FightWorld"), $processParts);nohup php app/app.php > /dev/null