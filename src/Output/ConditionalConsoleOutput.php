<?php

namespace JLaso\TradukojConnector\Output;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class ConditionalConsoleOutput extends AbstractOutput
{
    protected $condition;

    public function __construct($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @param $sprintf
     */
    public function write($sprintf)
    {
        if ($this->condition) {
            $result = call_user_func_array("sprintf", func_get_args());

            print $result;
        }
    }
}
