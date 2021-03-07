<?php
/*
 * TODO: нужно изменить алгоритм работы сервера. Сервер должен получать сообщение от нового клиента и форкать процесс.
 * В противном случае (как сейчас), работа сервера возможна только с одним клиентом
 */
use WebSocket\Server;

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

$options = array_merge([
    'port'          => 8000,
    'timeout'       => 1,
    'filter'        => ['text', 'binary', 'ping', 'pong'],
], getopt('', ['port:', 'timeout:', 'debug']));

$server = new Server($options);
do {
    while ($server->accept()) {
        try {
            do {
                $server->send(getData(), "text", false);
                sleep(1);
            } while(true);
        } catch (\Throwable $e) {
            echo "ERROR: {$e->getMessage()} [{$e->getCode()}]\n";
        }
    }
} while(true);