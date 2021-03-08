<?php

use Workerman\Worker;
use \Workerman\lib\Timer;

require __DIR__ . '/vendor/autoload.php';

function getData()
{
    $data = [];
    $db = new PDO('mysql:host=' . DATABASE_HOST . ';charset=utf8', DATABASE_USER_LOGIN, DATABASE_USER_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->query("USE ".DATABASE_NAME);

    $stmt = $db->query("SELECT id, block FROM blocks ORDER BY id DESC LIMIT 0, 10");
    $blocks = $stmt->fetchAll();

    if (empty($blocks)) {
        return false;
    }
    $lastBlockId = $blocks[0]["id"];

    $getLastHashsQuery = "SELECT blockId, transactionHash FROM transactions WHERE blockId = :blockId";
    $stmt = $db->prepare($getLastHashsQuery);
    $stmt->execute(["blockId" => $lastBlockId]);
    $hashes = $stmt->fetchAll();

    $data["hashs"] = $hashes;
    $data["blocks"] = $blocks;

    return json_encode($data);
}

$ws_worker = new Worker('websocket://127.0.0.1:2346');
$ws_worker->count = 1;

$ws_worker->onConnect = function ($connection) {
    Timer::add(1, function () use ($connection) {
        $connection->send(getData());
    });
    echo "New connection\n";
};

$ws_worker->onClose = function ($connection) {
    echo "Connection closed\n";
};

Worker::runAll();