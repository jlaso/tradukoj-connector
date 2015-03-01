<?php

namespace JLaso\TradukojConnector\PostClient;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
interface PostClientInterface
{
    public function call($url, $data = array());
}
