<?php

namespace JLaso\TradukojConnector\Protocol;
use JLaso\TradukojConnector\Model\Block;
use JLaso\TradukojConnector\Socket\SocketInterface;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
abstract class AbstractBaseProtocolAdapter implements ProtocolAdapterInterface
{
    /** @var  SocketInterface */
    protected $socket;

    /**
     * @param SocketInterface $socket
     */
    public function __construct(SocketInterface $socket)
    {
        $this->socket = $socket;
    }

    function sendMessage($msg)
    {
        // TODO: Implement sendMessage() method.
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

}