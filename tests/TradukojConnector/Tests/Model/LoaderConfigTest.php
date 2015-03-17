<?php

namespace JLaso\TradukojConnector\Tests\Model;

use JLaso\TradukojConnector\Model\ConfigLoader\ArrayConfigLoader;

class LoaderConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testArrayLoaderConfig()
    {
        $configArray = array(
            'project_id' => 1,
            'key' => 'key',
            'secret' => 'secret',
            'url' => 'https://localhost/api/',
        );

        $config = ArrayConfigLoader::load($configArray);

        $this->assertEquals($configArray['project_id'], $config->getProjectId());
        $this->assertEquals($configArray['key'], $config->getKey());
        $this->assertEquals($configArray['secret'], $config->getSecret());
        $this->assertEquals($configArray['url'], $config->getEndpoint());
    }

    /**
     * @expectedException \JLaso\TradukojConnector\Exception\InvalidConfigException
     */
    public function testInvalidConfigException()
    {
        $config = ArrayConfigLoader::load(array());
    }
}
