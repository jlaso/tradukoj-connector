<?php

namespace JLaso\TradukojConnector\PostClient;

use JLaso\TradukojConnector\Exception\NullPostResponseException;

/**
 * @author Joseluis Laso <jlaso@joseluislaso.es>
 */
class PostCurl extends AbstractPostClient
{
    public function call($url, $data = array())
    {
        $hdl = curl_init($url);
        $postFields = json_encode($data);
        curl_setopt($hdl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($hdl, CURLOPT_HTTPHEADER, array('Accept: json'));
        curl_setopt($hdl, CURLOPT_TIMEOUT, 10);
        curl_setopt($hdl, CURLOPT_POST, true);
        curl_setopt($hdl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($hdl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($hdl, CURLINFO_CONTENT_TYPE, 'application_json');
        curl_setopt($hdl, CURLOPT_SSL_VERIFYPEER, false);

        $body = curl_exec($hdl);
        $info = curl_getInfo($hdl);
        curl_close($hdl);

        $result = json_decode($body, true);
        if (!count($result)) {
            throw new NullPostResponseException(print_r($info, true).$body);
        }

        return $result;
    }
}
