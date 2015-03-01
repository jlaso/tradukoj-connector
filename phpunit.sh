/usr/bin/env sh

# start the echo simple socket server in order to test sockets

php tests/TradukojConnector/SocketServer/simple-server.php & phpunit

# shutdown socket simple server

echo "shutdown\n" | nc localhost 13337
