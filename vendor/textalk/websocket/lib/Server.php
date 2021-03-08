<?php
/*
 * TODO: нужно изменить алгоритм работы сервера. Сервер должен получать сообщение от нового клиента и форкать процесс.
 * В противном случае (как сейчас), работа сервера возможна только с одним клиентом
 */
use Workerman\Worker;

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
$ws_worker = new Worker('websocket://0.0.0.0:2346');
$ws_worker->count = 1;

// Emitted when new connection come
$ws_worker->onConnect = function ($connection) {
    \Workerman\lib\Timer::add(1, function () use ($connection) {
        $connection->send(getData($connection));
    });
    echo "New connection\n";
};

// Emitted when connection closed
$ws_worker->onClose = function ($connection) {
    echo "Connection closed\n";
};

// Run worker
Worker::runAll();