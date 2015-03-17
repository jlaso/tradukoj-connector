<?php

namespace JLaso\TradukojConnector;

use JLaso\TradukojConnector\Exception\BlockSizeSocketException;
use JLaso\TradukojConnector\Exception\ClientNotInitializedException;
use JLaso\TradukojConnector\Exception\CreateSocketException;
use JLaso\TradukojConnector\Exception\NullSocketResponseException;
use JLaso\TradukojConnector\Exception\SignatureSocketException;
use JLaso\TradukojConnector\Exception\SocketException;
use JLaso\TradukojConnector\Exception\SocketReadException;
use JLaso\TradukojConnector\Model\ClientApiConfig;
use JLaso\TradukojConnector\Output\NullOutput;
use JLaso\TradukojConnector\Output\OutputInterface;
use JLaso\TradukojConnector\Protocol\GzProtocolAdapter;
use JLaso\TradukojConnector\Protocol\LzfProtocolAdapter;
use JLaso\TradukojConnector\Protocol\ProtocolAdapterInterface;
use JLaso\TradukojConnector\Protocol\RawProtocolAdapter;
use JLaso\TradukojConnector\Socket\SocketInterface;
use JLaso\TradukojConnector\PostClient\PostClientInterface;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class ClientSocketApi
{
    /** @var SocketInterface */
    protected $socket;
    /** @var PostClientInterface */
    protected $postClient;
    /** @var ClientApiConfig */
    protected $clientApiConfig;
    /** @var  OutputInterface */
    protected $debugOutput;
    /** @var  OutputInterface */
    protected $output;
    /** @var  ProtocolAdapterInterface */
    protected $protocolAdapter;

    protected $init = false;
    protected $debug;

    const DONT_WAIT_RESPONSE = false;
    const WAIT_RESPONSE = true;

//    const ACK = 'ACK';
//    const NO_ACK = 'NO-ACK';
//    const BLOCK_SIZE = 1024;

    const DEBUG = false;  // initial status of debug if not passed to constructor
    const COMPRESS = true;  // initial status of compress if not passed to constructor

    // create socket request endpoints
    const REQUEST_SOCKET_LZF = 'create-socket';
    const REQUEST_SOCKET_NON_LZF = 'create-socket-no-lzf';

    // service commands
    const SVC_SHUTDOWN = 'shutdown';
    const SVC_GET_CATALOG_INDEX = 'catalog-index';
    const SVC_UPDATE_MESSAGE_IF_NEWEST = 'update-message-if-newest';
    const SVC_UPDATE_COMMENT_IF_NEWEST = 'update-comment-if-newest';
    const SVC_UPLOAD_KEYS = 'upload-keys';
    const SVC_DOWNLOAD_KEYS = 'download-keys';
    const SVC_TRANSDOC_INDEX = 'transdoc-index';
    const SVC_TRANSDOC_SYNC = 'transdoc-sync';
    const SVC_TRANSDOC_GET = 'transdoc-get';
    const SVC_REGISTER = 'register';

    /**
     * @param ClientApiConfig     $clientApiConfig
     * @param SocketInterface     $socket
     * @param PostClientInterface $postClient
     * @param bool                $debug
     * @param bool                $compress
     */
    public function __construct(
        ClientApiConfig $clientApiConfig,
        SocketInterface $socket,
        PostClientInterface $postClient,
        OutputInterface $output,
        $debug = self::DEBUG,
        $compress = self::COMPRESS
    ) {
        $this->debug = $debug;
        $this->socket = $socket;
        $this->postClient = $postClient;
        $this->clientApiConfig = $clientApiConfig;
        $this->output = $output;

        $this->debugOutput = $debug ? $output : new NullOutput();

        // strategy to select the Protocol that fits the best
        switch(true){
            case !$compress:
                $this->protocolAdapter = new RawProtocolAdapter($socket, $this->debugOutput);
                break;

            case function_exists('lzf_compress'):
                $this->protocolAdapter = new LzfProtocolAdapter($socket, $this->debugOutput);
                break;

            default:
                $this->protocolAdapter = new GzProtocolAdapter($socket, $this->debugOutput);
                break;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        if ($this->init) {
            //$this->shutdown();
        }
    }

    /**
     * @param string $address
     * @param int    $port
     *
     * @throws CreateSocketException
     */
    public function init($address = '', $port = 0)
    {
        if ($this->init) {
            $this->socket->close();
        }
        if (!$address && !$port) {
            $info = $this->serverSocketRequest();

            if (!$info['result']) {
                throw new CreateSocketException(print_r($info, true));
            }
            $address = $info['host'];
            $port = $info['port'];
        }
        // wait to populate socket
        sleep(2);

        $this->debugOutput->write("Connecting with %s through port %d\n", trim($address), intval($port));

        $this->socket->connect(trim($address), intval($port));

        $out = trim($this->socket->read(2048, PHP_NORMAL_READ));
        // print welcome message
        $this->output->writeln($out);

        $this->init = true;
    }

    /**
     * Request to server a socket, if okay server returns with the url and port opened.
     *
     * @return mixed
     *
     * @throws CreateSocketException
     */
    protected function serverSocketRequest()
    {
        $base = function_exists('lzf_compress') ? self::REQUEST_SOCKET_LZF : self::REQUEST_SOCKET_NON_LZF;
        $url = sprintf("%s%s/%d", $this->clientApiConfig->getEndpoint(), $base, $this->clientApiConfig->getProjectId());
        $data = array(
            'key' => $this->clientApiConfig->getKey(),
            'secret' => $this->clientApiConfig->getSecret(),
        );
        $this->debugOutput->write("requesting '%s'", $url);

        return $this->postClient->call($url, $data);
    }

    /**
     * @return mixed
     */
    protected function readException()
    {
        $read = $this->socket->read(100, PHP_NORMAL_READ);
        $this->debugOutput->write($read);
        $result = json_decode($read, true);

        return $result['reason'];
    }

    /**
     * @param string $msg
     *
     * @return bool
     * @throws SocketException
     */
    protected function sendMessage($msg)
    {
        $this->debugOutput->write("|-- sending '%s'\n", $msg);

        if (false == $this->protocolAdapter->sendMessage($msg)){
            throw new SocketException(sprintf("Error sending message '%s", $msg));
        }

        return true;
    }

    /**
     * @param string $command
     * @param array  $data
     *
     * @return mixed
     *
     * @throws BlockSizeSocketException
     * @throws ClientNotInitializedException
     * @throws NullSocketResponseException
     * @throws SignatureSocketException
     * @throws SocketException
     * @throws SocketReadException
     */
    protected function callService($command, $data = array(), $waitReponse = self::WAIT_RESPONSE)
    {
        if (!$this->init) {
            throw new ClientNotInitializedException();
        }

        $data['auth.key'] = $this->clientApiConfig->getKey();
        $data['auth.secret'] = $this->clientApiConfig->getSecret();
        $data['command'] = $command;
        $data['project_id'] = $this->clientApiConfig->getProjectId();

        $msg = json_encode($data);

        $this->sendMessage($msg);

        if(self::WAIT_RESPONSE == $waitReponse) {

            $buffer = $this->protocolAdapter->readMessage();

            $this->debugOutput->write($buffer);

            $result = json_decode($buffer, true);
            if (!count($result)) {
                throw new NullSocketResponseException();
            }
            if(!$result['result']){
                $this->init = false; // server is in charge to close socket
                throw new SocketException($result['reason']);
            }

            return $result;
        }

        return array();
    }

    /**
     *  S E R V I C E   D E C L A R A T I O N S.
     */

    /**
     * @return mixed
     */
    public function shutdown()
    {
        $this->callService(self::SVC_SHUTDOWN, array(), self::DONT_WAIT_RESPONSE);
        $this->init = false;
    }


    /**
     * @return mixed
     */
    public function getCatalogIndex()
    {
        $result = $this->callService(self::SVC_GET_CATALOG_INDEX);

        if(isset($result['catalogs'])){
            return $result['catalogs'];
        }

        return array();
    }

    /**
     * @param string    $bundle
     * @param string    $key
     * @param string    $language
     * @param string    $message
     * @param \DateTime $lastModification
     *
     * @return mixed
     */
    public function updateMessageIfNewest($bundle, $key, $language, $message, \DateTime $lastModification)
    {
        return $this->callService(
            self::SVC_UPDATE_MESSAGE_IF_NEWEST,
            array(
                'bundle'            => $bundle,
                'key'               => $key,
                'language'          => $language,
                'message'           => $message,
                'last_modification' => $lastModification->format('c'),

            )
        );
    }

    /**
     * @param string    $bundle
     * @param string    $key
     * @param string    $comment
     * @param \DateTime $lastModification
     *
     * @return mixed
     */
    public function updateCommentIfNewest($bundle, $key, $comment, \DateTime $lastModification)
    {
        return $this->callService(
            self::SVC_UPDATE_COMMENT_IF_NEWEST,
            array(
                'bundle'            => $bundle,
                'key'               => $key,
                'comment'           => $comment,
                'last_modification' => $lastModification->format('c'),

            )
        );
    }

    /**
     * @param string $catalog
     * @param string $data
     *
     * @return mixed
     */
    public function uploadKeys($catalog, $data)
    {
        $this->debugOutput->write("sending %d keys in catalog %s\n", count($data), $catalog);

        return $this->callService(
            self::SVC_UPLOAD_KEYS,
            array(
                'catalog'    => $catalog,
                'data'       => $data,
            )
        );
    }

    /**
     * @param string $catalog
     *
     * @return mixed
     */
    public function downloadKeys($catalog)
    {
        return $this->callService(self::SVC_DOWNLOAD_KEYS, array('catalog' => $catalog));
    }

    /**
     * @return mixed
     */
    public function transDocIndex()
    {
        return $this->callService(self::SVC_TRANSDOC_INDEX);
    }

    /**
     * @param string    $bundle
     * @param string    $key
     * @param string    $locale
     * @param string    $transFile
     * @param string    $document
     * @param \DateTime $updatedAt
     *
     * @return mixed
     */
    public function transDocSync($bundle, $key, $locale, $transFile, $document, \DateTime $updatedAt)
    {
        return $this->callService(
            self::SVC_TRANSDOC_SYNC,
            array(
                'bundle'            => $bundle,
                'key'               => $key,
                'locale'            => $locale,
                'file_name'         => $transFile,
                'message'           => $document,
                'last_modification' => $updatedAt->format('c'),
            )
        );
    }

    /**
     * @return mixed
     */
    public function register()
    {
        return $this->callService(self::SVC_REGISTER);
    }
}
