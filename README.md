这是一个演示如何使用 `PHP` 的原生 `Socket` 扩展进行网络编程的示例。该示例包含了建立连接、发送和接收数据的基本操作。

# 环境要求

- `PHP` 8.1 或更高版本
- 安装了 `Socket` 扩展（通常在 PHP 默认安装中包含）

# 使用方法

- 命令行下, 执行 `composer` 命令安装:

```shell
composer require jundayw/socket
```

- 打开 `Server.php` 文件，修改以下代码中的 IP 地址和端口号：

```php
use Jundayw\Socket\SocketServer;

$server = new SocketServer('0.0.0.0', 8808);
//$server->setDomain(AF_INET);
//$server->setType(SOCK_STREAM);
//$server->setProtocol(SOL_TCP);
//$server->setOptions([
//    [SOL_SOCKET, SO_REUSEADDR, 1]
//]);
//$server->setAddress('0.0.0.0');
//$server->setPort(8808);
//$server->setLength(1024);
//$server->setFlags(MSG_WAITALL);

$server->encode = function ($buffer) {
    $buffer = mb_convert_encoding($buffer, 'GBK', 'utf8');
    return pack('a*', $buffer);
};

$server->decode = function ($buffer) {
    $buffer = unpack('a*', $buffer)[1];
    return mb_convert_encoding($buffer, 'utf8', 'GBK');
};

$server->onConnect = function ($socket) use ($server){
    $this->bind($socket, $uid = mt_rand(10000, 99999));
    $this->echo('Client connected', [$uid]);
};

$server->onMessage = function ($socket) use ($server){
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

$server->onDisconnect = function ($socket) use ($server){
    $this->echo('Client disconnected', [$this->getClientUID($socket)]);
    $this->removeBind($socket);
};

try {
    $server->run();
} catch (Exception $exception) {
    echo $exception->getMessage();
}
```

- 打开 `Client.php` 文件，修改以下代码中的 IP 地址和端口号：

```php
use Jundayw\Socket\SocketClient;


$client = new SocketClient('127.0.0.1', 8808);
//$client->setDomain(AF_INET);
//$client->setType(SOCK_STREAM);
//$client->setProtocol(SOL_TCP);
//$client->setOptions([
//    [SOL_SOCKET, SO_REUSEADDR, 1]
//]);
//$client->setAddress('0.0.0.0');
//$client->setPort(8808);
//$client->setLength(1024);
//$client->setFlags(MSG_WAITALL);

$client->encode = function ($buffer) {
    $buffer = mb_convert_encoding($buffer, 'GBK', 'utf8');
    return pack('a*', $buffer);
};

$client->decode = function ($buffer) {
    $buffer = unpack('a*', $buffer)[1];
    return mb_convert_encoding($buffer, 'utf8', 'GBK');
};

$client->onConnect = function ($socket) use ($client){
    echo "Connected to server 127.0.0.1:8808 ...\n";
};

$client->onMessage = function ($socket) use ($client){
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

$client->onDisconnect = function ($socket) use ($client){
    echo "disconnected to server 127.0.0.1:8808 ...\n";
};

try {
    $client->run();
} catch (Exception $exception) {
    echo $exception->getMessage();
}
```

- 启动服务器：

````php
php Server.php
````

- 运行客户端：

```php
php Client.php
```

- 在客户端终端中，你将看到成功建立连接并发送接收数据的输出。

# 注意事项

- 本示例仅用于演示目的，请不要在生产环境中直接使用。在实际开发中，需要考虑异常处理、错误处理和安全性等方面的更多问题。
- 如需了解更多关于 PHP Socket 扩展的详细信息，请参考 PHP 官方文档：[PHP: Sockets - Manual](https://www.php.net/manual/en/book.sockets.php)
