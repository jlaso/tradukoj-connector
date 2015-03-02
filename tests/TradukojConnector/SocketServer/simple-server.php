#!/usr/local/bin/php
<?php

/**
 * Excample taken from http://php.net/manual/en/function.socket-select.php
 *
 * thanks vardhan ( at ) rogers ( dot ) com
 */

$time = microtime(true);

$port = 13337;

$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($sock, 0, $port);
socket_listen($sock);

$clients = array($sock);

while (true) {
    $read = $clients;
    $write = NULL;
    $except = NULL;

    if(intval(microtime(true)-$time)>10){  // watchdog of 10 seconds
	socket_close($sock);
        exit(0);
    }

    if (socket_select($read, $write, $except, 1) < 1){
        continue;
    }

    // check if there is a client trying to connect
    if (in_array($sock, $read)) {
        // accept the client, and add him to the $clients array
        $clients[] = $newsock = socket_accept($sock);

        // remove the listening socket from the clients-with-data array
        $key = array_search($sock, $read);
        unset($read[$key]);
    }

    // loop through all the clients that have data to read from
    foreach ($read as $read_sock) {
        // read until newline or 1024 bytes
        // socket_read while show errors when the client is disconnected, so silence the error messages
        $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);

        // check if the client is disconnected
        if ($data === false) {
            // remove client for $clients array
            $key = array_search($read_sock, $clients);
            unset($clients[$key]);
            // continue to the next client to read from, if any
            continue;
        }

        // trim off the trailing/beginning white spaces
        $data = trim($data);

        // check if there is any data after trimming off the spaces
        if (!empty($data)) {
            $data .= "\n";
            if("shutdown\n"==$data){
                socket_close($sock);
                exit(0);
            }
            socket_write($read_sock, $data, strlen($data));
        }

    } // end of reading foreach
}

// close the listening socket
socket_close($sock);
