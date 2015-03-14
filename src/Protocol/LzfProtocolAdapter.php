<?php

namespace JLaso\TradukojConnector\Protocol;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class LzfProtocolAdapter extends RawProtocolAdapter
{

    /**
     * @param string $message
     * @return mixed
     */
    protected function preProcess($message)
    {
        return lzf_compress($message);
    }

    /**
     * @param string $message
     * @return mixed
     */
    protected function postProcess($message)
    {
        return lzf_decompress($message);
    }


}