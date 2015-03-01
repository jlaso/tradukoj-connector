<?php

namespace JLaso\TradukojConnector\Tests;

use JLaso\TradukojConnector\PostClient\PostCurl;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 *
 * Thanks http://www.jsontest.com/
 */
class PostCurlTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var PostCurl
     */
    protected $curl;

    public function setUp()
    {
        $this->curl = new PostCurl();
    }

    /**
     * @expectedException \JLaso\TradukojConnector\Exception\NullPostResponseException
     */
    public function testNullPostResponseException()
    {
        $this->curl->call('');
    }

    public function testCurl()
    {
        $result = $this->curl->call('http://echo.jsontest.com/key/value/one/two');

        $this->assertEquals(
            array(
                'one' => "two",
                'key' => "value",
            ),
            $result
        );
    }

}
