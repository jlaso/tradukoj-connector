<?php

namespace JLaso\TradukojConnector\Output;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
interface OutputInterface
{
    public function write($sprintf);

    public function writeln($sprintf);
}
