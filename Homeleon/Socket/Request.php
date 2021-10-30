<?php

namespace Homeleon\Socket;

class Request
{
    public $client = [];
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

        [$this->client['request_method'], $this->client['request_uri']] = strpos($line, ' ') !== false ? explode(' ', $line) : ['GET', $line];

        while ($line = rtrim(fgets($fd))) {
            if (preg_match('/^(\S+):\s+(.*)$/', $line, $matches)) {
                $this->client[$matches[1]] = $matches[2];
            } else {
                break;
            }
        }

        [$this->client['ip'], $this->client['port']] = explode(':', stream_socket_get_name($fd, true));

        if (!isset($this->client['Sec-WebSocket-Key'])) {
            return false;
        }
    }

    private function handshake($fd)
    {
        $upgradeKey = base64_encode(sha1($this->client['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $upgradeHeaders = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                "Upgrade: websocket\r\n" .
                "connection: Upgrade\r\n" .
                "Sec-WebSocket-Accept:".$upgradeKey."\r\n\r\n";
        fwrite($fd, $upgradeHeaders);
    }
}
