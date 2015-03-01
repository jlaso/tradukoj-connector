<?php

namespace JLaso\TradukojConnector\Tests;
use JLaso\TradukojConnector\Output\ConsoleOutput;


/**
 * @coversDefaultClass \JLaso\TradukojConnector\ClientSocketApi
 */
class ConsoleOutputTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ConsoleOutput
     */
    protected $consoleOutput;

    public function setUp()
    {
        $this->consoleOutput = new ConsoleOutput();
    }

    public function testWrite()
    {
        $this->expectOutputString('ABCDE');
        $this->consoleOutput->write('ABCDE');
    }

    public function testWriteln()
    {
        $this->expectOutputString('ABCDE'.PHP_EOL);
        $this->consoleOutput->writeln('ABCDE');
    }

}
