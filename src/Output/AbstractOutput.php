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
        $this->write($sprintf.PHP_EOL);
    }
}
