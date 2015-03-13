<?php

namespace JLaso\TradukojConnector\Protocol;
use JLaso\TradukojConnector\Model\Block;
use JLaso\TradukojConnector\Socket\SocketInterface;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
abstract class AbstractBaseProtocolAdapter implements ProtocolAdapterInterface
{
    const MAX_ERRORS = 5;

    /** @var  SocketInterface */
    protected $socket;

    /**
     * @param SocketInterface $socket
     */
    public function __construct(SocketInterface $socket)
    {
        $this->socket = $socket;
    }

    function sendMessage($message)
    {
        $message = $this->preProcess($message);
        $blocks = $this->splitMessageInBlocks($message);
        $numBlocks = count($blocks);
        $errors = 0;
        while(count($blocks) && ($errors <= self::MAX_ERRORS)){

            $block = array_shift($blocks);
            $msg = $this->getSignatureBlock($block, $numBlocks).$block->getContent();
            $this->socket->write($msg);
            $read = $this->socket->read($this->getMaxBlockSize());
            switch($read){
                case $this->getACK():
                    break;

                case $this->getNOACK():
                    
                    break;

                default:
                    break;
            }
        }
    }

    function readMessage()
    {
        // TODO: Implement readMessage() method.
    }

    /**
     * @param Block $block
     * @param int $numBlocks
     *
     * @return string
     */
    protected function getSignatureBlock(Block $block, $numBlocks)
    {
        return sprintf("%06d:%03d:%03d:", $block->getLength(), $block->getOrder(), $numBlocks);
    }

    /**
     * @param string $message
     *
     * @return string;
     */
    protected function preProcess($message)
    {
        return $message;
    }

    /**
     * @param string $message
     *
     * @return Block[]
     */
    protected function splitMessageInBlocks($message)
    {

    }



}