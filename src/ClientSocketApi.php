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
use JLaso\TradukojConnector\Output\OutputInterface;
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
    protected $output;

    protected $init = false;
    protected $debug;

    const DONT_WAIT_RESPONSE = false;
    const WAIT_RESPONSE = true;

    const ACK    = 'ACK';
    const NO_ACK = 'NO-ACK';
    const BLOCK_SIZE = 1024;

    const DEBUG = false;  // initial status of debug if not passed to constructor

    // create socket request endpoints
    const REQUEST_SOCKET_LZF = 'create-socket';
    const REQUEST_SOCKET_NON_LZF = 'create-socket-no-lzf';

    // service commands
    const SVC_SHUTDOWN = 'shutdown';
    //const SVC_GET_BUNDLE_INDEX = 'bundle-index';
    const SVC_GET_CATALOG_INDEX = 'catalog-index';
    //const SVC_GET_KEY_INDEX = 'key-index';
    //const SVC_GET_TRANSLATIONS = 'translations';
    //const SVC_GET_TRANSLATION_DETAILS = 'translation-details';
    //const SVC_GET_COMMENT = 'get-comment';
    //const SVC_PUT_MESSAGE = 'put-message';
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
     */
    public function __construct(
        ClientApiConfig $clientApiConfig,
        SocketInterface $socket,
        PostClientInterface $postClient,
        OutputInterface $output,
        $debug = self::DEBUG
    ) {
        $this->debug = $debug;
        $this->socket = $socket;
        $this->postClient = $postClient;
        $this->clientApiConfig = $clientApiConfig;
        $this->output = $output;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        if ($this->init) {
            $this->shutdown();
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
        if (!$address && !$port) {
            $info = $this->serverSocketRequest();

            if (!$info['result']) {
                throw new CreateSocketException(print_r($info, true));
            }
            $address = $info['host'];
            $port = $info['port'];
        }

        if ($this->init) {
            $this->socket->close();
        }
        // wait to populate socket
        sleep(2);

        $this->sprintfIfDebug("Connecting with %s through port %d\n", trim($address), intval($port));

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

        return $this->postClient->call($url, $data);
    }

    /**
     * @param string $message
     *
     * @return string
     */
    protected function compress($message)
    {
        return function_exists('lzf_compress') ? lzf_compress($message) :  gzcompress($message, 9);
    }

    /**
     * @param string $message
     *
     * @return string
     */
    protected function uncompress($message)
    {
        return function_exists('lzf_compress') ? lzf_decompress($message) : gzuncompress($message);
    }

    protected function sprintfIfDebug($msg)
    {
        if ($this->debug) {
            call_user_func_array(array($this->output, "write"), func_get_args());
        }
    }

    /**
     * @param string $msg
     * @param bool   $compress
     *
     * @return bool
     *
     * @throws SocketException
     */
    protected function sendMessage($msg, $compress = true)
    {
        $this->sprintfIfDebug("|-- sending '%s'\n", $msg);

        $msg = $compress ? $this->compress($msg) : $msg.PHP_EOL;
        $len = strlen($msg);

        $this->sprintfIfDebug("|\tsending %d chars\n", $len);

        $blocks = ceil($len / self::BLOCK_SIZE);
        for ($i = 0; $i<$blocks; $i++) {
            $block = substr(
                $msg,
                $i * self::BLOCK_SIZE,
                ($i == $blocks-1) ? $len - ($i-1) * self::BLOCK_SIZE : self::BLOCK_SIZE
            );
            $prefix = sprintf("%06d:%03d:%03d:", strlen($block), $i+1, $blocks);
            $aux = $prefix.$block;

            $this->sprintfIfDebug("|\t\tsending block %d of %d, prefix = %s\n", $i+1, $blocks, $prefix);

            if (false === $this->socket->write($aux)) {
                throw new SocketException();
            };

            do {
                $read = $this->socket->read(10, PHP_NORMAL_READ);
                $this->sprintfIfDebug("|\tR: ".$read);
                if(0 === strpos($read, self::NO_ACK)){
                    return false;
                }
                if(0 === strpos($read, self::ACK)){
                    break;
                }
            } while ($read);
        }

        return true;
    }

    /**
     * @param bool $compress
     *
     * @return string
     *
     * @throws BlockSizeSocketException
     * @throws SignatureSocketException
     * @throws SocketReadException
     */
    protected function readSocket($compress = true)
    {
        $this->sprintfIfDebug("\n-------readSocket-------\n\n");
        $buffer = '';
        $overload = strlen('000000:000:000:');
        do {
            $buf = $this->socket->read($overload + self::BLOCK_SIZE, PHP_BINARY_READ);
            if (false === $buf) {
                throw new SocketReadException($this->socket->lastErrorAsString());
            }

            if (!trim($buf)) {
                return '';
            }

            $this->sprintfIfDebug(sprintf("Received '%s'\n", $buf));

            if (substr_count($buf, ":") < 3) {
                throw new SignatureSocketException('error in format');
            }

            list($size, $block, $blocks) = explode(":", $buf);

            $aux = substr($buf, $overload);

            $this->sprintfIfDebug("\t(*) received block %d of %d (start of block `%s`)\n", $block, $blocks, substr($aux, 0, 20));

            if(!$this->debug) {
                $this->output->write('R');
            }

            if ($size == strlen($aux)) {
                $this->socket->write(self::ACK);
            } else {
                $this->socket->write(self::NO_ACK);
                throw new BlockSizeSocketException(
                    sprintf(
                        'error in size (block %d of %d): informed %d vs %d read',
                        $block, $blocks, $size, strlen($aux)
                    )
                );
            }

            $buffer .= $aux;
        } while ($block < $blocks);

        $result = $compress ? $this->uncompress($buffer) : $buffer;

        if ($this->debug) {
            $aux = json_decode($result, true);
            if (isset($aux['data'])) {
                $this->output->writeln("received %d keys", count($aux['data']));
            }
        }

        return $result;
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

        $msg = json_encode($data).PHP_EOL;

        $this->sendMessage($msg);

        if(self::WAIT_RESPONSE == $waitReponse) {
            $buffer = $this->readSocket();

            if ($this->debug) {
                print $buffer;
            }

            $result = json_decode($buffer, true);
            if (!count($result)) {
                throw new NullSocketResponseException();
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

//    /**
//     * @return mixed
//     */
//    public function getBundleIndex()
//    {
//        return $this->callService(self::SVC_GET_BUNDLE_INDEX);
//    }

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

//    /**
//     * @param string $bundle
//     *
//     * @return mixed
//     */
//    public function getKeyIndex($bundle)
//    {
//        return $this->callService(self::SVC_GET_KEY_INDEX, array('bundle' => $bundle));
//    }

//    /**
//     * @param string $bundle
//     * @param string $key
//     *
//     * @return mixed
//     */
//    public function getMessages($bundle, $key)
//    {
//        return $this->callService(
//            self::SVC_GET_TRANSLATIONS,
//            array(
//                'bundle'     => $bundle,
//                'key'        => $key,
//            )
//        );
//    }
//
//    /**
//     * @param string $bundle
//     * @param string $key
//     * @param string $locale
//     *
//     * @return mixed
//     */
//    public function getMessage($bundle, $key, $locale)
//    {
//        return $this->callService(
//            self::SVC_GET_TRANSLATION_DETAILS,
//            array(
//                'bundle'     => $bundle,
//                'key'        => $key,
//                'language'   => $locale,
//            )
//        );
//    }

//    /**
//     * @param string $bundle
//     * @param string $key
//     *
//     * @return mixed
//     */
//    public function getComment($bundle, $key)
//    {
//        return $this->callService(
//            self::SVC_GET_COMMENT,
//            array(
//                'bundle'     => $bundle,
//                'key'        => $key,
//            )
//        );
//    }
//
//    /**
//     * @param string $bundle
//     * @param string $key
//     * @param string $language
//     * @param string $message
//     *
//     * @return mixed
//     */
//    public function putMessage($bundle, $key, $language, $message)
//    {
//        return $this->callService(
//            self::SVC_PUT_MESSAGE,
//            array(
//                'bundle'     => $bundle,
//                'key'        => $key,
//                'language'   => $language,
//                'message'    => $message,
//            )
//        );
//    }

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
        $this->sprintfIfDebug("sending %d keys in catalog %s\n", count($data), $catalog);

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
