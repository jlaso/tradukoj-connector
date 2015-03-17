<?php

namespace JLaso\TradukojConnector\Model\ConfigLoader;

use JLaso\TradukojConnector\Model\ClientApiConfig;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
interface ConfigLoaderInterface
{
    /**
     * @param $source
     *
     * @return ClientApiConfig
     */
    public static function load($source);
}
