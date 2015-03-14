<?php

namespace JLaso\TradukojConnector\Protocol;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class RawProtocolAdapter extends AbstractBaseProtocolAdapter
{
    /**
     * @return int
     */
    public function getMaxBlockSize()
    {
        return 1024;
    }

    /**
     * @return string
     */
    function getNOACK()
    {
        return "NO-ACK";
    }

    /**
     * @return string
     */
    function getACK()
    {
        return "ACK";
    }
}