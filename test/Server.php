<?php

use Jundayw\Socket\SocketServer;

include './../../../autoload.php';

$server = new SocketServer('0.0.0.0', 8808);

$server->encode = function ($buffer) {
    $buffer = mb_convert_encoding($buffer, 'GBK', 'utf8');
    return pack('a*', $buffer);
};

$server->decode = function ($buffer) {
    $buffer = unpack('a*', $buffer)[1];
    return mb_convert_encoding($buffer, 'utf8', 'GBK');
};

$server->onConnect = function ($socket) {
    $this->bind($socket, $uid = mt_rand(10000, 99999));
    $this->echo('Client connected', [$uid]);
};

$server->onMessage = function ($socket) {
    $message = $this->read($socket);
    $this->echo('Received message from', [$uid = $this->getClientUID($socket)], $message);
    // // 广播消息给所有客户端
    // foreach ($this->getClients() as $client) {
    //     if ($client == $this->getMaster()) {
    //         continue;
    //     }
    //     $this->write($client, $message);
    // }
    // // 给指定用户发消息
    // foreach ($this->getConnections() as $connection) {
    //     if ($connection->getUID() == $uid) {
    //         $this->write($connection->socket(), $message);
    //     }
    // }
    // 给当前用户发消息
    $this->write($socket, $message);
};

$server->onDisconnect = function ($socket) {
    $this->echo('Client disconnected', [$this->getClientUID($socket)]);
    $this->removeBind($socket);
};

try {
    $server->run();
} catch (Exception $exception) {
    echo $exception->getMessage();
}
