<?php
use WebSocket\Server;

require __DIR__ . '/vendor/autoload.php';

$client = new WebSocket\Client("ws://localhost:8000");
//$client->text("Hello WebSocket.org!");
do {
    $message = json_decode($client->receive(), true);
    print_r($message["hashs"]);
    //echo $client->receive()."\r\n";
    sleep(1);
} while(true);
//var_dump();

$client->close();