<?php

namespace JLaso\TradukojConnector\Protocol;
use JLaso\TradukojConnector\Model\Block;
use JLaso\TradukojConnector\Socket\SocketInterface;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
interface ProtocolAdapterInterface
{
    /**
     * @param SocketInterface $socket
     */
    function __construct(SocketInterface $socket);

    /**
     * @return int
     */
    function getMaxBlockSize();

    /**
     * @param mixed $msg
     *
     * @return bool
     */
    function sendMessage($msg);

    /**
     * @return bool
     */
    function readMessage();

}
