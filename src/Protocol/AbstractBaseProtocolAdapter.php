<?php

namespace JLaso\TradukojConnector\Protocol;

use JLaso\TradukojConnector\Exception\BlockSizeSocketException;
use JLaso\TradukojConnector\Exception\SignatureSocketException;
use JLaso\TradukojConnector\Exception\SocketException;
use JLaso\TradukojConnector\Model\Block;
use JLaso\TradukojConnector\Output\OutputInterface;
use JLaso\TradukojConnector\Socket\SocketInterface;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
abstract class AbstractBaseProtocolAdapter implements ProtocolAdapterInterface
{
    const MAX_ERRORS = 5;

    /** @var  SocketInterface */
    protected $socket;

    /** @var  OutputInterface */
    protected $debugOutput;

    /**
     * @param SocketInterface $socket
     */
    public function __construct(SocketInterface $socket, OutputInterface $debugOutput)
    {
        $this->socket = $socket;
        $this->debugOutput = $debugOutput;
    }

    /**
     * @param string $message
     *
     * @return bool
     * @throws SocketException
     */
    function sendMessage($message)
    {
        $lenPrev = strlen($message);
        $message = $this->preProcess($message);
        $lenNow = strlen($message);

        $blocks = $this->splitMessageInBlocks($message);
        $numBlocks = count($blocks);
        $errors = 0;
        $overload = $this->getSignatureSize()*$numBlocks;

        $this->debugOutput->writeln(
            "Sending %d bytes into a package of %d bytes with compressing ratio of %d%%",
            $lenPrev,
            $lenNow,
            100-intval(100*($overload+$lenNow)/$lenPrev)
        );

        while(count($blocks)){

            $block = array_shift($blocks);
            $signature = $this->getSignatureBlock($block, $numBlocks);
            $msg = $signature.$block->getContent();

            $this->debugOutput->write("\t[SND] %s ",$signature);

            $this->socket->write($msg);

            $read = $this->socket->read(25, PHP_NORMAL_READ);

            $this->debugOutput->writeln(" [%s]", $read);

            switch($read){
                case ($this->getACK()):
                    break;

                case ($this->getNOACK()):
                    $exception = $this->socket->read(100, PHP_NORMAL_READ);
                    throw new SocketException($exception);
/*
                    array_push($blocks, $block);
                    if($errors++>self::MAX_ERRORS){
                        return false;
                    }
                    break;
*/

                default:
                    throw new SocketException(sprintf("Unrecognized acknowledgment '%s'", $read));
            }
        }

        return true;
    }

    /**
     * @return string
     * @throws SignatureSocketException
     * @throws SocketException
     */
    function readMessage()
    {
        /** @var Block[] $blocks */
        $blocks = array();
        $signatureSize = $this->getSignatureSize();
        $errors = 0;

        do {

            $read = $this->socket->read($signatureSize + $this->getMaxBlockSize(), PHP_BINARY_READ);

            $signature = substr($read, 0, $signatureSize);

            $this->debugOutput->write("\t[RCV] %s ", $signature);

            if (substr_count($signature, ":") < 3) {
                throw new SignatureSocketException('error in format');
            }

            list($size, $nBlock, $numBlocks) = explode(":", $signature);
            $size = intval($size);
            $nBlock = intval($nBlock);
            $numBlocks = intval($numBlocks);

            $buffer = substr($read, $signatureSize);

            if ($size == strlen($buffer)) {
                $this->debugOutput->writeln(" OK");
                $this->sendACK();
                $blocks[$nBlock] = new Block($nBlock, $buffer);
            } else {
                $this->debugOutput->writeln(" NOT OK");
                $this->sendNOACK();
                if($errors++>self::MAX_ERRORS){
                    throw new SocketException(sprintf("Too much retries to receive a block, aborting. Currently '%d' retries.", $errors));
                }
            }

        } while($numBlocks < count($blocks));

        // sort blocks before to join them
        ksort($blocks);
        $message = "";
        foreach($blocks as $block){
            $message .= $block->getContent();
        }

        return $this->postProcess($message);
    }

    /**
     * Send ACK to sender
     */
    protected function sendACK()
    {
        $this->socket->write($this->getACK().PHP_EOL);
    }

    /**
     * Send NO-ACK to sender
     */
    protected function sendNOACK()
    {
        $this->socket->write($this->getNOACK().PHP_EOL);
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
     * @return int
     */
    protected function getSignatureSize()
    {
        return strlen($this->getSignatureBlock(new Block(0, ""), 0));
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
     * @return string;
     */
    protected function postProcess($message)
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
        $blocks = array();
        $i = 1;
        $parts = str_split($message, $this->getMaxBlockSize());

        foreach ($parts as $part) {
            $blocks[] = new Block($i++, $part);
        }

        return $blocks;
    }


}