<?php

file_put_contents(__DIR__ . '/../resources/event_manager_pid.txt', getmypid());
$redis = new Redis;
$redis->pconnect('127.0.0.1', '6379');
$fp = stream_socket_client("tcp://192.168.0.101:8080", $errno,$errstr);

if (!$fp) {
    file_put_contents(__DIR__ . '/../resources/log.txt', "Error: $errno - $errstr\n");
    exit;
} else {
    fwrite($fp,
            "GET / HTTP/1.1\n" .
            "Sec-WebSocket-Key: ".generateRandomString(16)."\r\n" .
            "event-key: 123\r\n\r\n"
    );
    while (true) {
        sleep(5);

        $t = str_replace('.', '', round(microtime(true), 4));
        if (!empty($events = $redis->zrangebyscore('events', '-inf', $t))) {
            $redis->zremrangebyscore('events', '-inf', $t);
            $result = fwrite($fp, encode(json_encode(['ev' => $events]), 'text', true));

            if ($result === false) {
                fclose($fp);
                echo "Server refused. I'm terminate";exit;
            }
        }
    }
}

fclose($fp);



function encode($payload, $type = 'text', $masked = false) {
    $frameHead = array();
    $payloadLength = strlen($payload);

    switch ($type) {
        case 'text':
            // first byte indicates FIN, Text-Frame (10000001):
            $frameHead[0] = 129;
            break;

        case 'close':
            // first byte indicates FIN, Close Frame(10001000):
            $frameHead[0] = 136;
            break;

        case 'ping':
            // first byte indicates FIN, Ping frame (10001001):
            $frameHead[0] = 137;
            break;

        case 'pong':
            // first byte indicates FIN, Pong frame (10001010):
            $frameHead[0] = 138;
            break;
    }

    // set mask and payload length (using 1, 3 or 9 bytes)
    if ($payloadLength > 65535) {
        $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
        $frameHead[1] = ($masked === true) ? 255 : 127;
        for ($i = 0; $i < 8; $i++) {
            $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
        }
        // most significant bit MUST be 0
        if ($frameHead[2] > 127) {
            return array('type' => '', 'payload' => '', 'error' => 'frame too large (1004)');
        }
    } elseif ($payloadLength > 125) {
        $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
        $frameHead[1] = ($masked === true) ? 254 : 126;
        $frameHead[2] = bindec($payloadLengthBin[0]);
        $frameHead[3] = bindec($payloadLengthBin[1]);
    } else {
        $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
    }

    // convert frame-head to string:
    foreach (array_keys($frameHead) as $i) {
        $frameHead[$i] = chr($frameHead[$i]);
    }
    if ($masked === true) {
        // generate a random mask:
        $mask = array();
        for ($i = 0; $i < 4; $i++) {
            $mask[$i] = chr(rand(0, 255));
        }

        $frameHead = array_merge($frameHead, $mask);
    }
    $frame = implode('', $frameHead);

    // append payload to frame:
    for ($i = 0; $i < $payloadLength; $i++) {
        $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
    }

    return $frame;
}

function generateRandomString($length = 20) {
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 3)), 0, $length);
}

function removeTimeDot(){
    return str_replace('.', '', round(microtime(true), 4));
}
