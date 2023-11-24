<?php

namespace Jundayw\Socket;

use Exception;
use Jundayw\Socket\Protocols\SocketProtocol;
use Socket;

class SocketServer extends SocketProtocol
{
    private array $clients = [];        // 客户端连接迭代器
    private array $connections = [];    // 连接迭代器

    public function __construct(string $address = '0.0.0.0', int $port = 8808)
    {
        $this->setAddress($address);
        $this->setPort($port);
    }

    /**
     * @return array
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    /**
     * 连接迭代器
     *
     * @return array
     */
    public function getConnections(): array
    {
        return $this->connections;
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
            if (!call_user_func_array('socket_set_option', $option)) {
                throw new Exception("Failed to set socket option: " . socket_strerror(socket_last_error()));
            }
        }
        // 将 Socket 绑定到一个特定的地址和端口
        if (!socket_bind($this->getMaster(), $this->getAddress(), $this->getPort())) {
            throw new Exception("Failed to bind socket: " . socket_strerror(socket_last_error()));
        }
        // 将 Socket 设置为监听模式
        if (!socket_listen($this->getMaster())) {
            throw new Exception("Failed to listen on socket: " . socket_strerror(socket_last_error()));
        }

        while (true) {
            $sockets = [$this->getMaster()];
            $write   = $except = null;

            $sockets = array_merge($sockets, $this->getClients());

            if (socket_select($sockets, $write, $except, null) === false) {
                throw new Exception("Failed to select socket: " . socket_strerror(socket_last_error()));
            }

            // 接收客户端的连接
            if (in_array($this->getMaster(), $sockets)) {
                if ($client = socket_accept($this->getMaster())) {
                    $this->clients[] = $client;
                    $this->onConnect($client);
                }
                continue;
            }

            foreach ($sockets as $socket) {
                try {
                    // 接收客户端的数据
                    if (socket_recv($socket, $buffer, $this->getLength(), MSG_PEEK) === false || is_null($buffer)) {
                       throw new Exception('socket_close');
                    }
                }catch (Exception $exception){
                    $this->onDisconnect($socket);
                    $this->clients = array_filter($this->clients, function ($client) use ($socket) {
                        return $client != $socket;
                    }, ARRAY_FILTER_USE_BOTH);
                    // 断开连接
                    socket_close($socket);
                    continue;
                }
                $this->onMessage($socket);
            }
        }
        $this->clients = [];
        socket_close($this->getMaster());
    }

    public function getPeerName(Socket $socket): ?string
    {
        if (!socket_getpeername($socket, $address, $port)) {
            return null;
        }
        return md5($address . $port);
    }

    /**
     * 绑定 UID 到连接
     *
     * @param Socket $socket
     * @param int $uid
     * @param mixed $alias
     * @return object|null
     */
    public function bind(Socket $socket, int $uid, mixed $alias = null): ?object
    {
        if (is_null($connection = $this->getPeerName($socket))) {
            return null;
        }
        return $this->connections[$connection] = new class($socket, $uid, $alias) {
            public function __construct(
                private readonly Socket $socket,
                private readonly int    $uid,
                private readonly mixed  $alias,
            )
            {
                //
            }

            public function socket(): Socket
            {
                return $this->socket;
            }

            public function getUID(): int
            {
                return $this->uid;
            }

            public function getAlias(): mixed
            {
                return $this->alias;
            }
        };
    }

    /**
     * 解绑 UID 与连接
     *
     * @param Socket $socket
     * @return array
     */
    public function removeBind(Socket $socket): array
    {
        if (is_null($connection = $this->getPeerName($socket))) {
            return $this->connections;
        }
        return $this->connections = array_filter($this->connections, function ($key) use ($connection) {
            return $key != $connection;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * 获取 UID
     *
     * @param Socket $socket
     * @return int|null
     */
    public function getClientUID(Socket $socket): ?int
    {
        if (is_null($connection = $this->getPeerName($socket)) || !array_key_exists($connection, $this->connections)) {
            return null;
        }
        return $this->connections[$connection]?->getUID();
    }

    /**
     * 获取 Alias
     *
     * @param Socket $socket
     * @return mixed
     */
    public function getClientAlias(Socket $socket): mixed
    {
        if (is_null($connection = $this->getPeerName($socket)) || !array_key_exists($connection, $this->connections)) {
            return null;
        }
        return $this->connections[$connection]?->getAlias();
    }

    /**
     * 通过 UID 获取 Socket
     *
     * @param int|null $uid
     * @return Socket|null
     */
    public function getSocketByClientUID(int $uid = null): ?Socket
    {
        foreach ($this->connections as $connection) {
            if ($connection->getUID() == $uid) {
                return $connection->socket();
            }
        }
        return null;
    }

    /**
     * 通过 Alias 获取 Socket
     *
     * @param $alias
     * @return Socket|null
     */
    public function getSocketByClientAlias($alias = null): ?Socket
    {
        foreach ($this->connections as $connection) {
            if ($connection->getAlias() == $alias) {
                return $connection->socket();
            }
        }
        return null;
    }
}
