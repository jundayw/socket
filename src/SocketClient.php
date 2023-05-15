<?php

namespace Jundayw\Socket;

use Exception;
use Jundayw\Socket\Protocols\SocketProtocol;

class SocketClient extends SocketProtocol
{
    public function __construct(string $address = '127.0.0.1', int $port = 8808)
    {
        $this->setAddress($address);
        $this->setPort($port);
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        // 创建主 Socket
        if (($this->master = socket_create($this->getDomain(), $this->getType(), $this->getProtocol())) === false) {
            throw new Exception("Failed to create socket: " . socket_strerror(socket_last_error()));
        }
        // 设置套接字的套接字选项
        foreach ($this->getOptions() as $option) {
            array_unshift($option, $this->getMaster());
            if (call_user_func_array('socket_set_option', $option) === false) {
                throw new Exception("Failed to set socket option: " . socket_strerror(socket_last_error()));
            }
        }
        // 连接到指定的地址和端口
        if (socket_connect($this->getMaster(), $this->getAddress(), $this->getPort()) === false) {
            throw new Exception("Failed to connect socket: " . socket_strerror(socket_last_error()));
        }

        $this->onConnect($this->getMaster());

        $this->onMessage($this->getMaster());

        $this->onDisconnect($this->getMaster());

        socket_close($this->getMaster());
    }
}
