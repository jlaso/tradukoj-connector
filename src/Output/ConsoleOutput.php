<?php

namespace JLaso\TradukojConnector\Output;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class ConsoleOutput extends AbstractOutput
{
    /**
     * @param $sprintf
     */
    public function write($sprintf)
    {
        $result = call_user_func_array("sprintf", func_get_args());

        print $result;
    }
}
