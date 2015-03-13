<?php

namespace JLaso\TradukojConnector\Model;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class Block
{
    protected $order;

    protected $content;

    function __construct($order, $content)
    {
        $this->order   = $order;
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param mixed $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return strlen($this->getContent());
    }

}