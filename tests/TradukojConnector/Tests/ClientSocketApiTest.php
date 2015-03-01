<?php

namespace JLaso\TradukojConnector\Tests;

use JLaso\TradukojConnector\ClientSocketApi;
use JLaso\TradukojConnector\Model\Loader\ArrayLoader;
use JLaso\TradukojConnector\Output\ConsoleOutput;
use JLaso\TradukojConnector\Output\NullOutput;
use JLaso\TradukojConnector\Socket\SocketInterface;
use JLaso\TradukojConnector\PostClient\PostClientInterface;

class ClientSocketApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClientSocketApi
     */
    protected $clientSocketApi;

    protected function getConfigArray()
    {
        return array(
            'project_id' => 1,
            'key' => 'key',
            'secret' => 'secret',
            'url' => 'https://localhost/api/',
        );
    }

    public static function postClientCallMock($url, $data)
    {
        return array(
            'result' => false,
            'host' => 'locahost',
            'port' => 10000,
        );
    }

    public function setUp()
    {
        /** @var SocketInterface $socket */
        $socket = $this->getMock('JLaso\\TradukojConnector\\Socket\\SocketInterface');
        $socket
            ->method('write')
            ->will($this->returnCallback(function ($msg, $len) { return $len; }))
        ;
        $socket
            ->method('read')
            ->will($this->returnCallback(
                function ($len, $type) {

                    switch ($len) {
                        case 10:
                            return ClientSocketApi::ACK.PHP_EOL;

                        case 2048:
                            return "Welcome!";

                        default:
                            $val = '{"result":false,"reason":"test"}'.PHP_EOL;
                            $val = function_exists('lzf_compress') ? lzf_compress($val) : gzcompress($val);

                            return sprintf("%06d:001:001:%s", strlen($val), $val);
                    }
                }
            ))
        ;

        $loader = new ArrayLoader();
        $config = $loader->load($this->getConfigArray());

        $postClient = $this->getMock('JLaso\\TradukojConnector\\PostClient\\PostClientInterface');
        $postClient
            //->expects($this->atLeastOnce())
            ->method('call')
            ->will($this->returnCallback(
                function ($url, $data) {
                    return ClientSocketApiTest::postClientCallMock($url, $data);
                }
            ))
        ;

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

    protected static function getProperty($name)
    {
        $class = new \ReflectionClass('JLaso\\TradukojConnector\\ClientSocketApi');
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property;
    }

    public function testCreateSocket()
    {
        $serverSocketRequest = self::getMethod('serverSocketRequest');
        $result = $serverSocketRequest->invoke($this->clientSocketApi);

        $this->assertFalse($result['result']);
    }

    /**
     * @expectedException \JLaso\TradukojConnector\Exception\CreateSocketException
     */
    public function testCreateSocketException()
    {
        $this->clientSocketApi->init();
    }

    public function testCompress()
    {
        $serverSocketRequest = self::getMethod('compress');
        $data = str_repeat('abcde', 10);
        $compressed = $serverSocketRequest->invokeArgs($this->clientSocketApi, array($data));

        $this->assertLessThan(strlen($data), strlen($compressed));

        $serverSocketRequest = self::getMethod('uncompress');
        $uncompressed = $serverSocketRequest->invokeArgs($this->clientSocketApi, array($compressed));

        $this->assertGreaterThan(strlen($compressed), strlen($uncompressed));

        $this->assertEquals($data, $uncompressed);
    }

    public function testSprintfIfDebug()
    {
        $debug = self::getProperty('debug');
        $debug->setValue($this->clientSocketApi, true);

        $this->assertTrue($debug->getValue($this->clientSocketApi));

        $consoleOutput = new ConsoleOutput();
        $output = self::getProperty('output');
        $output->setValue($this->clientSocketApi, $consoleOutput);

        $sprintfIfDebug = self::getMethod('sprintfIfDebug');
        $this->expectOutputString('test');
        $sprintfIfDebug->invokeArgs($this->clientSocketApi, array('test'));
    }

    public function testServiceCalls()
    {
        //        $debug = self::getProperty('debug');
//        $debug->setValue($this->clientSocketApi, true);
//
//        $consoleOutput = new ConsoleOutput();
//        $output = self::getProperty('output');
//        $output->setValue($this->clientSocketApi, $consoleOutput);

        $init = $this->getProperty('init');
        $init->setValue($this->clientSocketApi, true);

        $result = $this->clientSocketApi->getBundleIndex();
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->getCatalogIndex();
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->getKeyIndex("messages");
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->getMessages("messages", "test.key");
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->getMessage("messages", "test.key", "en");
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->getComment("messages", "test.key");
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->putMessage("messages", "test.key", "en", "test");
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->updateMessageIfNewest("messages", "test.key", "en", "test", new \DateTime());
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->updateCommentIfNewest("messages", "test.key", "test", new \DateTime());
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->uploadKeys("messages", array());
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->downloadKeys("messages");
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->transDocIndex();
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->transDocSync("messages", "test.key", "en", "file", "document", new \DateTime());
        $this->assertFalse($result['result']);

        $result = $this->clientSocketApi->register();
        $this->assertFalse($result['result']);

        $shutdown = $this->getMethod('shutdown');
        $result = $shutdown->invoke($this->clientSocketApi);
        $this->assertFalse($result['result']);

        // the shutdown method switches init to false
        $init = $this->getProperty('init');
        $this->assertFalse($init->getValue($this->clientSocketApi));
    }
}
