<?php

use TransactionParser\Parser;

require __DIR__ . '/vendor/autoload.php';

try {
    $parser = new Parser();
    $parser->exec();
} catch(\Throwable $e) {
    echo $e->getMessage();
} catch(\PDOException $e) {
    echo $e->getMessage();
}