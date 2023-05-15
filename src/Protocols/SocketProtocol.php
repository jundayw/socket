<?php

namespace Jundayw\Socket\Protocols;

use Closure;
use Socket;

abstract class SocketProtocol
{
    private int $domain = AF_INET;
    private int $type = SOCK_STREAM;
    private int $protocol = SOL_TCP;

    private array $options = [
        [SOL_SOCKET, SO_REUSEADDR, 1],
    ];

    private string $address = '0.0.0.0';
    private int $port = 0;

    private int $length = 1024;
    private int $flags = 0;

    protected Socket $master;

    public ?Closure $encode = null;
    public ?Closure $decode = null;

    public ?Closure $onConnect = null;         // 连接监听回调函数
    public ?Closure $onMessage = null;         // 数据监听回调函数
    public ?Closure $onDisconnect = null;      // 断开连接监听回调函数

    /**
     * @return int
     */
    public function getDomain(): int
    {
        return $this->domain;
    }

    /**
     * @param int $domain
     * @return SocketProtocol
     */
    public function setDomain(int $domain): static
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return SocketProtocol
     */
    public function setType(int $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getProtocol(): int
    {
        return $this->protocol;
    }

    /**
     * @param int $protocol
     * @return SocketProtocol
     */
    public function setProtocol(int $protocol): static
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return SocketProtocol
     */
    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return SocketProtocol
     */
    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return SocketProtocol
     */
    public function setPort(int $port): static
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @param int $length
     * @return SocketProtocol
     */
    public function setLength(int $length): static
    {
        $this->length = $length;
        return $this;
    }

    /**
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @param int $flags
     * @return SocketProtocol
     */
    public function setFlags(int $flags): static
    {
        $this->flags = $flags;
        return $this;
    }

    /**
     * @return Socket
     */
    public function getMaster(): Socket
    {
        return $this->master;
    }

    /**
     * 打包
     *
     * @param $buffer
     * @return mixed
     */
    public function encode($buffer): mixed
    {
        if (is_callable($this->encode)) {
            return call_user_func($this->encode, $buffer);
        }
        return $buffer;
    }

    /**
     * 解包
     *
     * @param $buffer
     * @return mixed
     */
    public function decode($buffer): mixed
    {
        if (is_callable($this->decode)) {
            return call_user_func($this->decode, $buffer);
        }
        return $buffer;
    }

    /**
     * 数据写入
     *
     * @param Socket $socket
     * @param mixed $data
     * @return void
     */
    public function write(Socket $socket, mixed $data): void
    {
        socket_write($socket, $data = $this->encode($data), strlen($data));
    }

    /**
     * 数据读取
     *
     * @param Socket $socket
     * @return mixed
     */
    public function read(Socket $socket): mixed
    {
        if (socket_recv($socket, $buffer, $this->getLength(), $this->getFlags()) === false || is_null($buffer)) {
            return null;
        }
        return $this->decode($buffer);
    }

    /**
     * 连接监听
     *
     * @param Socket $socket
     * @return void
     */
    public function onConnect(Socket $socket): void
    {
        if (is_callable($this->onConnect)) {
            call_user_func(Closure::bind($this->onConnect, $this), $socket);
        }
    }

    /**
     * 数据监听
     *
     * @param Socket $socket
     * @return void
     */
    public function onMessage(Socket $socket): void
    {
        if (is_callable($this->onMessage)) {
            call_user_func(Closure::bind($this->onMessage, $this), $socket);
        }
    }

    /**
     * 断开连接监听
     *
     * @param Socket $socket
     * @return void
     */
    public function onDisconnect(Socket $socket): void
    {
        if (is_callable($this->onDisconnect)) {
            call_user_func(Closure::bind($this->onDisconnect, $this), $socket);
        }
    }

    public function echo(): void
    {
        $buffer = [];
        foreach (func_get_args() as $arg) {
            $buffer[] = is_array($arg) ? '[' . implode(',', $arg) . ']' : $arg;
        }
        echo implode(' ', $buffer) . PHP_EOL;
    }
}
