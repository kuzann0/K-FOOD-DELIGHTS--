<?php
require_once 'notification_config.php';
require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$pusher = new Ratchet\WebSocket\WsServer(
    new Ratchet\Wamp\WampServer(
        new Pusher\Pusher(
            PUSHER_APP_KEY,
            PUSHER_APP_SECRET,
            PUSHER_APP_ID,
            array('cluster' => PUSHER_APP_CLUSTER)
        )
    )
);

$server = new Ratchet\Http\HttpServer(
    new Ratchet\WebSocket\WsServer($pusher)
);

$socket = new React\Socket\Server(WEBSOCKET_HOST . ':' . WEBSOCKET_PORT, $loop);
new Ratchet\Server\IoServer($server, $socket, $loop);

echo "WebSocket server running on " . WEBSOCKET_HOST . ":" . WEBSOCKET_PORT . "\n";

$loop->run();