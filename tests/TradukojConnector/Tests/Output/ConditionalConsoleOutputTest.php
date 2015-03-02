<?php

namespace JLaso\TradukojConnector\Tests;

use JLaso\TradukojConnector\Output\ConditionalConsoleOutput;

/**
 * @coversDefaultClass \JLaso\TradukojConnector\ClientSocketApi
 */
class ConditionalConsoleOutputTest extends \PHPUnit_Framework_TestCase
{
    public function testWriteIfTrue()
    {
        $output = new ConditionalConsoleOutput(true);
        $this->expectOutputString('ABCDE');
        $output->write('ABCDE');
    }

    public function testWritelnIfTrue()
    {
        $output = new ConditionalConsoleOutput(true);
        $this->expectOutputString('ABCDE'.PHP_EOL);
        $output->writeln('ABCDE');
    }

    public function testWriteIfFalse()
    {
        $output = new ConditionalConsoleOutput(false);
        $this->expectOutputString('');
        $output->write('ABCDE');
    }

    public function testWritelnIfFalse()
    {
        $output = new ConditionalConsoleOutput(false);
        $this->expectOutputString('');
        $output->writeln('ABCDE');
    }
}
