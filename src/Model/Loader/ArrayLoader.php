<?php

namespace JLaso\TradukojConnector\Model\Loader;

use JLaso\TradukojConnector\Exception\InvalidConfigException;
use JLaso\TradukojConnector\Model\ClientApiConfig;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class ArrayLoader implements LoaderInterface
{
    /**
     * @param array $source
     *
     * @return ClientApiConfig
     *
     * @throws InvalidConfigException
     */
    public static function load($source = array())
    {
        $config = new ClientApiConfig();
        try {
            $config->setKey($source['key']);
            $config->setSecret($source['secret']);
            $config->setEndpoint($source['url']);
            $config->setProjectId($source['project_id']);
        } catch (\Exception $e) {
            throw new InvalidConfigException($e->getMessage());
        }

        return $config;
    }
}
