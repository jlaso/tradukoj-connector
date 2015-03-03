<?php

namespace JLaso\TradukojConnector\Socket;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
interface SocketInterface
{
    public function __construct();

    public function connect($address, $port = 0);

    public function read($length, $type = PHP_BINARY_READ);

    public function write($buffer, $length = 0);

    public function close();

    public function lastError();

    public function lastErrorAsString();
}
