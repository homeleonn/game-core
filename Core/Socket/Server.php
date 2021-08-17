<?php

namespace Core\Socket;

use Closure;

error_reporting(E_ALL); //Выводим все ошибки и предупреждения
set_time_limit(0);		//Время выполнения скрипта не ограничено
ob_implicit_flush();	//Включаем вывод без буферизации
ignore_user_abort(true);//Выключаем зависимость от пользователя

class Server 
{
	private $ip;
	private $port;
	private $socket;
	private $fds = [];
	private $fdCounter = 0; // Счетчик всех соединений
	private $events = ['start', 'open', 'close', 'message'];
	private $eventsHandlers = [];

	public function __construct($ip = "127.0.0.1", $port = '8080')
	{
		$this->ip 	= $ip;
		$this->port = $port;
	}

	public function on(string $event, callable $callback)
	{
		if (!in_array($event, $this->events)) {
			echo "Unknown event: {$event}\n";
		}

		$this->eventsHandlers[$event] = $callback;
	}

	public function start()
	{
		$this->socket = stream_socket_server(sprintf('tcp://%s:%s', $this->ip, $this->port), $errno, $errstr);
		$this->applyEventHandler('start');

		while (true) {
			$read = $this->fds;
			$read[-1] = $this->socket;

			if (!stream_select($read, $write, $except, null)) break;

			if (isset($read[-1])) {
				$this->open(stream_socket_accept($this->socket, -1));
				unset($read[-1]);
			}

			if (!empty($read)) {
				array_walk($read, [$this, 'processingConnection']);
			}
		}
	}

	private function message($frame)
	{
		$this->applyEventHandler('message', [$frame]);
	}

	private function open($fd)
	{
		$this->fdCounter++;
		$this->fds[(int)$fd] = $fd;
		$request = new Request($fd, $this);
		$this->applyEventHandler('open', [$request]);
	}

	public function close($fd, $fdId = null)
	{
		if ($fdId) $fd = $this->getClient($fdId);
		$fdId = (int)$fd;
		$this->applyEventHandler('close', [$fdId]);
		unset($this->fds[$fdId]);
		fclose($fd);
	}

	public function push($fd, $message)
	{
		if (!$this->fds[$fd]) {
			// var_dump(debug_backtrace());
			echo "Connection null", __LINE__, "\n";return;

		}
		fwrite($this->fds[$fd], Frame::encode($message));
	}

	public function applyEventHandler(string $handlerName, array $arguments = [])
	{
		if (isset($this->eventsHandlers[$handlerName])) {
			array_unshift($arguments, $this);
			return call_user_func_array($this->eventsHandlers[$handlerName], $arguments);
		}
	}


	private function processingConnection($fd)
	{
		$frame = new Frame($fd);
		$frame->dec();

		if (!$frame->data || $frame->type == 'close') {
			$this->close($fd);
			return false;
		}

		$this->message($frame);
	}

	public function getClientList()
	{
		return $this->fds;
	}

	public function getClient($fdId)
	{
		return $this->fds[$fdId];
	}

	public function getIp()
	{
		return $this->ip;
	}

	public function getPort()
	{
		return $this->port;
	}
}