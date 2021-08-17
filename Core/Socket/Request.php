<?php

namespace Core\Socket;

class Request 
{
	public $server = [];
	public $fd;

	public function __construct($fd, $server)
	{
		$this->fd = (int)$fd;
		$this->parseRequest($fd, $server);
		$this->handshake($fd);
	}

	private function parseRequest($fd, $server)
	{
		$line = fgets($fd);
		[$this->server['request_method'], $this->server['request_uri']] = strpos($line, ' ') !== false ? explode(' ', $line) : ['GET', $line];
		
		while ($line = rtrim(fgets($fd))) {
			if (preg_match('/^(\S+):\s+(.*)$/', $line, $matches)) {
				$this->server[$matches[1]] = $matches[2];
			} else {
				break;
			}
		}
		
		[$this->server['ip'], $this->server['port']] = explode(':', stream_socket_get_name($fd, true));
		
		if (!isset($this->server['Sec-WebSocket-Key'])) {
			return false;
		}
	}

	private function handshake($fd)
	{
		$upgradeKey = base64_encode(sha1($this->server['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		$upgradeHeaders = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
				"Upgrade: websocket\r\n" .
				"connection: Upgrade\r\n" .
				"Sec-WebSocket-Accept:".$upgradeKey."\r\n\r\n";
		fwrite($fd, $upgradeHeaders);
	}
}