<?php

namespace JLaso\TradukojConnector\Tests;

use JLaso\TradukojConnector\Output\NullOutput;

/**
 * @coversDefaultClass \JLaso\TradukojConnector\ClientSocketApi
 */
class NullOutputTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NullOutput
     */
    protected $nullOutput;

    public function setUp()
    {
        $this->nullOutput = new NullOutput();
    }

    public function testWrite()
    {
        $this->expectOutputString('');
        $this->nullOutput->write('ABCDE');
    }
}
