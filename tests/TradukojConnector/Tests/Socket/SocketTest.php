<?php

namespace JLaso\TradukojConnector\Tests;

use JLaso\TradukojConnector\Socket\Socket;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class SocketTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Socket
     */
    protected $socket;

    public function setUp()
    {
        $this->socket = new Socket();
    }

    /**
     * @expectedException \JLaso\TradukojConnector\Exception\CreateSocketException
     */
    public function testCreateSocketException()
    {
        $this->socket->create(null, null, null);
    }

    /**
     * @expectedException \JLaso\TradukojConnector\Exception\CreateSocketException
     */
    public function testCreateSocketException2()
    {
        $this->socket->create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->socket->connect('127.0.0.1');
    }

    /**
     * @expectedException \JLaso\TradukojConnector\Exception\CreateSocketException
     */
    public function testCreateSocketException3()
    {
        //$this->socket->create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->socket->connect('127.0.0.1');
    }

//    public function testSocket()
//    {
//        $this->socket->create(AF_INET, SOCK_STREAM, SOL_TCP);
//        $this->socket->connect('localhost', 13337);
//
//        $this->socket->write("HELLO!");
//        $result = $this->socket->read(4096, PHP_NORMAL_READ);
//        $this->assertEquals("HELLO!\n", $result);
//
//        $lastError = $this->socket->lastError();
//        $this->assertEquals(0, $lastError);
//
//        $lastError = $this->socket->lastErrorAsString();
//        $this->assertEquals('Undefined error: 0', $lastError);
//
//        $this->socket->close();
//    }
}
