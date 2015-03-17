<?php

namespace JLaso\TradukojConnector\Protocol;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class GzProtocolAdapter extends RawProtocolAdapter
{
    /**
     * @param string $message
     * @return mixed
     */
    protected function preProcess($message)
    {
        return gzcompress($message, 9);
    }

    /**
     * @param string $message
     * @return mixed
     */
    protected function postProcess($message)
    {
        return gzuncompress($message);
    }

}