<?php

namespace JLaso\TradukojConnector\Model;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class ClientApiConfig
{
    /**
     * @var string
     */
    protected $key;
    /**
     * @var string
     */
    protected $secret;
    /**
     * @var string
     */
    protected $endpoint;
    /**
     * @var int
     */
    protected $projectId;

    public function __construct()
    {
        $this->key = '';
        $this->secret = '';
        $this->endpoint = '';
        $this->projectId = 0;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param mixed $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @return mixed
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param mixed $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }
}
