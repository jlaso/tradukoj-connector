<?php

namespace JLaso\TradukojConnector\Output;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
abstract class AbstractOutput implements OutputInterface
{
    /**
     * @param $sprintf
     */
    public function writeln($sprintf)
    {
        $args = func_get_args();
        $args[0] .= PHP_EOL;

        call_user_func_array(array($this, 'write'), $args);
    }
}
