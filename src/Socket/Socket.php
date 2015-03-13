<?php

namespace JLaso\TradukojConnector\Socket;

use JLaso\TradukojConnector\Exception\CreateSocketException;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class Socket implements SocketInterface
{
    protected $socket;

    public function __construct()
    {
        try {
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        } catch (\Exception $e) {
            throw new CreateSocketException($e->getMessage());
        }
    }

    /**
     * @param $address
     * @param int $port
     *
     * @throws CreateSocketException
     */
    public function connect($address, $port = 0)
    {
        try {
            socket_connect($this->socket, $address, $port);
        } catch (\Exception $e) {
            throw new CreateSocketException($e->getMessage());
        }
    }

    /**
     * @param $length
     * @param int $type
     *
     * @return string
     */
    public function read($length, $type = PHP_BINARY_READ)
    {
        return socket_read($this->socket, $length, $type);
    }

    /**
     * @param $buffer
     * @param int $length
     *
     * @return int
     */
    public function write($buffer, $length = 0)
    {
        //if ("\n" != substr($buffer, -1)) {
        //    $buffer .= "\n";
        //}
        if (!$length) {
            $length = strlen($buffer);
        }

        return socket_write($this->socket, $buffer, $length);
    }

    public function close()
    {
        socket_close($this->socket);
    }

    /**
     * @return int
     */
    public function lastError()
    {
        return socket_last_error($this->socket);
    }

    /**
     * @return string
     */
    public function lastErrorAsString()
    {
        if($error = socket_last_error($this->socket)) {
            return socket_strerror($error);
        }

        return "Success";
    }
}
