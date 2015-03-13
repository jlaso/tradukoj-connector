<?php

namespace JLaso\TradukojConnector\Protocol;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class LzfProtocolAdapter extends AbstractBaseProtocolAdapter
{
    /**
     * @return int
     */
    public function getMaxBlockSize()
    {
        return 1024;
    }

}