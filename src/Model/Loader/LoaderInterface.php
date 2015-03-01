<?php

namespace JLaso\TradukojConnector\Model\Loader;

use JLaso\TradukojConnector\Model\ClientApiConfig;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
interface LoaderInterface
{
    /**
     * @param $source
     *
     * @return ClientApiConfig
     */
    public function load($source);
}
