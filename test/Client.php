<?php

use Jundayw\Socket\SocketClient;

include './../../../autoload.php';

$server = new SocketClient('127.0.0.1', 8808);

$server->encode = function ($buffer) {
    $buffer = mb_convert_encoding($buffer, 'GBK', 'utf8');
    return pack('a*', $buffer);
};

$server->decode = function ($buffer) {
    $buffer = unpack('a*', $buffer)[1];
    return mb_convert_encoding($buffer, 'utf8', 'GBK');
};

$server->onConnect = function ($socket) {
    echo "Connected to server 127.0.0.1:8808 ...\n";
};

$server->onMessage = function ($socket) {
    while (true) {
        echo 'input: ' . PHP_EOL;
        $message = fgets(STDIN);
        $message = str_replace(PHP_EOL, '', $message);

        if (empty($message)) {
            continue;
        }

        if ($message == 'q') {
            break;
        }

        echo 'input message: ' . $message . PHP_EOL;
        // 发送消息
        $this->write($socket, $message);
        // 接收服务器应答消息
        if (is_null($buffer = $this->read($socket))) {
            echo 'socket_read() failed: ' . socket_strerror(socket_last_error()) . PHP_EOL;
            break;
        }
        // 打印应答消息内容
        echo 'Received reply message: ' . $buffer . PHP_EOL;
    }
};

$server->onDisconnect = function ($socket) {
    echo "disconnected to server 127.0.0.1:8808 ...\n";
};

try {
    $server->run();
} catch (Exception $exception) {
    echo $exception->getMessage();
}
