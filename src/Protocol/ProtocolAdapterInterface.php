<?php

namespace JLaso\TradukojConnector\Protocol;

use JLaso\TradukojConnector\Output\OutputInterface;
use JLaso\TradukojConnector\Socket\SocketInterface;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
interface ProtocolAdapterInterface
{
    /**
     * @param SocketInterface $socket
     */
    public function __construct(SocketInterface $socket, OutputInterface $output);

    /**
     * @return int
     */
    function getMaxBlockSize();

    /**
     * @return string
     */
    function getACK();

    /**
     * @return string
     */
    function getNOACK();

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
