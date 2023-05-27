<?php

use Jundayw\Socket\SocketServer;

include './../../../autoload.php';

$server = new SocketServer('0.0.0.0', 8080);
$server->setLength(1024 * 8);
$server->encode = function ($buffer) {
    $dataLength = strlen($buffer);
    $header     = '';

    // 设置数据帧的FIN位和操作码
    $header .= chr(129);

    // 判断数据帧长度并设置相应的标记位
    if ($dataLength <= 125) {
        $header .= chr($dataLength);
    } elseif ($dataLength <= 65535) {
        $header .= chr(126) . pack('n', $dataLength);
    } else {
        $header .= chr(127) . pack('Q', $dataLength);
    }

    return $header . $buffer;
};

$server->decode = function ($buffer) use ($server) {
    $decodedData = '';
    $length      = ord($buffer[1]) & 127;
    if ($length === 126) {
        $masks  = substr($buffer, 4, 4);
        $buffer = substr($buffer, 8);
    } elseif ($length === 127) {
        $masks  = substr($buffer, 10, 4);
        $buffer = substr($buffer, 14);
    } else {
        $masks  = substr($buffer, 2, 4);
        $buffer = substr($buffer, 6);
    }
    for ($i = 0; $i < strlen($buffer); ++$i) {
        $decodedData .= $buffer[$i] ^ $masks[$i % 4];
    }
    return $decodedData;
};

$server->onConnect = function ($socket) {
    $this->echo('Client connected');

    $headers = [];
    $request = socket_read($socket, 1024);

    // 解析HTTP请求头
    $lines = preg_split("/\r\n/", $request);
    foreach ($lines as $line) {
        $line = chop($line);
        if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }

    // 获取WebSocket的Sec-WebSocket-Key
    $secWebSocketKey = $headers['Sec-WebSocket-Key'];
    $magic           = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    // 生成握手响应头
    $responseHeader = "HTTP/1.1 101 Switching Protocols\r\n";
    $responseHeader .= "Upgrade: websocket\r\n";
    $responseHeader .= "Connection: Upgrade\r\n";
    $responseHeader .= "Sec-WebSocket-Accept: " . base64_encode(sha1($secWebSocketKey . $magic, true)) . "\r\n";
    $responseHeader .= "\r\n";

    // 发送握手响应头给客户端
    socket_write($socket, $responseHeader);
};

$server->onMessage = function ($socket) {
    $message = $this->read($socket);
    var_dump($message);
    // 给当前用户发消息
    $this->write($socket, $message);
};

$server->onDisconnect = function ($socket) {
    $this->echo('Client disconnected');
};

try {
    $server->run();
} catch (Exception $exception) {
    echo $exception->getMessage();
}
