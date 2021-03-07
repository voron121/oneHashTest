<?php

namespace TransactionParser;

use Exception;
use PDO;
use WebSocket;


// TODO: сделать проверку на error в json с ответом от сервера а не на наличие элементов в result
class Parser
{
    protected $client;
    protected $checkedBlock = [];

    public function __construct()
    {
        $headers = [
            "Content-Type: application/json"
        ];
        $this->client = new WebSocket\Client(INFURA_WEBSOCKET.INFURA_PROJECT_ID, ["headers" => $headers]);
    }

    /**
     * @return mixed
     */
    protected function getBlock() : string
    {
        $blockNumber = "";
        $request = [
            "jsonrpc" => "2.0",
            "method" => "eth_blockNumber",
            "params" => [],
            "id" =>  1
        ];
        $this->client->text(json_encode($request));
        $lastBlock = json_decode($this->client->receive(), true);
        if (!empty($lastBlock) && isset($lastBlock["result"])) {
            $blockNumber = $lastBlock["result"];
        }
        return $blockNumber;
    }

    /**
     * @return array
     */
    protected function getTransactions(string $block) : array
    {
        $transactions = [];
        $request = [
            "jsonrpc" => "2.0",
            "method" => "eth_getBlockByNumber",
            "params" => [$block,false],
            "id" =>  1
        ];

        $this->client->text(json_encode($request));
        $response = json_decode($this->client->receive(), true);

        if (!empty($response) && isset($response["result"]["transactions"])) {
            $transactions = $response["result"]["transactions"];
        }
        return $transactions;
    }

    /**
     * @param string $block
     * @param array $transactions
     */
    protected function writeTransaction(string $block, array $transactions) : void
    {
        $db = new PDO('mysql:host=' . DATABASE_HOST . ';charset=utf8', DATABASE_USER_LOGIN, DATABASE_USER_PASSWORD);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->query("USE ".DATABASE_NAME);

        $blockInsertQuery = "INSERT INTO blocks SET block = :block";
        $transactionsInsertQuery = "INSERT INTO transactions SET blockId = :blockId, transactionHash = :transactionHash";
        $stmt = $db->prepare($blockInsertQuery);
        $transactionStmt = $db->prepare($transactionsInsertQuery);

        try {
            $db->beginTransaction();
            $stmt->execute(["block" => $block]);
            $lastBlockId = $db->lastInsertId();
            if (is_null($lastBlockId)) {
                throw new Exception("Error write block");
            }
            foreach ($transactions as $transactionHash) {
                $transactionStmt->execute([
                    "blockId" => $lastBlockId,
                    "transactionHash" => $transactionHash
                ]);
            }
            $db->commit();
        } catch(Exception $e) {
            echo $e->getMessage();
            $db->rollBack();
        }
        unset($db);
    }

    // TODO: реализовать очистку таблиц перед запуском
    // TODO: реализовать отправку в серверу сообщения о том что есть новый блок для запроса к БД (дабы уменьшить запросы к БД. Использовать соккеты)
    public function exec() : void
    {
        do {
            $blockNumber    = $this->getBlock();
            $transactions   = $this->getTransactions($blockNumber);
            if (""  === $blockNumber || isset($this->checkedBlock[$blockNumber]) || empty($transactions)) {
                continue;
            }
            $this->writeTransaction($blockNumber, $transactions);
            $this->checkedBlock[$blockNumber] = true;
        } while(true);
    }
}