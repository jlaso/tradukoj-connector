<?php

namespace JLaso\TradukojConnector\Tests;


use JLaso\TradukojConnector\ClientSocketApi;
use JLaso\TradukojConnector\Model\Loader\ArrayLoader;
use JLaso\TradukojConnector\Output\NullOutput;
use JLaso\TradukojConnector\Socket\SocketInterface;
use JLaso\TradukojConnector\PostClient\PostClientInterface;


/**
 * @coversDefaultClass \JLaso\TradukojConnector\ClientSocketApi
 */
class ClientReadSocketTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ClientSocketApi
     */
    protected $clientSocketApi;

    protected $buffer;

    /**
     * @param mixed $buffer
     */
    public function setBuffer($buffer)
    {
        $this->buffer = $buffer;
    }

    /**
     * @return mixed
     */
    public function getBuffer()
    {
        if(is_array($this->buffer)){
            return array_shift($this->buffer);
        }

        return $this->buffer;
    }


    protected function getConfigArray()
    {
        return array(
            'project_id' => 1,
            'key' => 'key',
            'secret' => 'secret',
            'url' => 'https://localhost/api/'
        );
    }

    public function readSocketMock($length)
    {
        return $this->getBuffer();
    }

    public function setUp()
    {
        /** @var SocketInterface $socket */
        $socket = $this->getMock('JLaso\\TradukojConnector\\Socket\\SocketInterface');
        $socket
            ->expects($this->atLeastOnce())
            ->method('read')
            ->will($this->returnCallback(function ($length) { return $this->readSocketMock($length); }))
        ;

        $loader = new ArrayLoader();
        $config = $loader->load($this->getConfigArray());

        $postClient = $this->getMock('JLaso\\TradukojConnector\\PostClient\\PostClientInterface');

        $nullOutput = new NullOutput();

        $this->clientSocketApi = new ClientSocketApi($config, $socket, $postClient, $nullOutput);
    }

    protected static function getMethod($name)
    {
        $class = new \ReflectionClass('JLaso\\TradukojConnector\\ClientSocketApi');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @expectedException \JLaso\TradukojConnector\Exception\SocketReadException
     */
    public function testReadSocketWithSocketReadException()
    {
        $method = self::getMethod('readSocket');
        $compress = false;
        $this->setBuffer(false);
        $method->invokeArgs($this->clientSocketApi, array($compress));
    }

    /**
     * @expectedException \JLaso\TradukojConnector\Exception\SignatureSocketException
     */
    public function testReadSocketWithSignatureSocketException()
    {
        $method = self::getMethod('readSocket');
        $compress = false;
        $this->setBuffer('1');
        $method->invokeArgs($this->clientSocketApi, array($compress));
    }

    /**
     * @expectedException \JLaso\TradukojConnector\Exception\BlockSizeSocketException
     */
    public function testReadSocketWithBlockSizeSocketException()
    {
        $method = self::getMethod('readSocket');
        $compress = false;
        $this->setBuffer('000002:001:001:A');  // size of block (2) does not match with real size (str_len('A'))
        $method->invokeArgs($this->clientSocketApi, array($compress));
    }

    public function testBlankReadSocket()
    {
        $method = self::getMethod('readSocket');
        $compress = false;
        $this->setBuffer('');
        $result = $method->invokeArgs($this->clientSocketApi, array($compress));

        $this->assertEquals('', $result);
    }

    public function testReadSocket()
    {
        $method = self::getMethod('readSocket');
        $compress = false;

        $this->setBuffer('000000:000:000:');
        $result = $method->invokeArgs($this->clientSocketApi, array($compress));
        $this->assertEquals('', $result);

        $this->setBuffer('000001:001:001:A');
        $result = $method->invokeArgs($this->clientSocketApi, array($compress));
        $this->assertEquals('A', $result);

        $this->setBuffer(array('000001:001:002:A','000001:002:002:B'));
        $result = $method->invokeArgs($this->clientSocketApi, array($compress));
        $this->assertEquals('AB', $result);

        $this->setBuffer(array('000001:001:003:A','000001:002:003:B','000002:003:003:CD'));
        $result = $method->invokeArgs($this->clientSocketApi, array($compress));
        $this->assertEquals('ABCD', $result);
    }

}
