<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Assert\Assertion;
use TailEventStream\Consumer;

$streamFilePath = getenv('STREAM_FILE_PATH');
Assertion::string($streamFilePath);

$consumer = new Consumer($streamFilePath);

$consumer->consume(function (string $messageType, $data) {
    echo $messageType . ': ' . var_export($data, true) . "\n";
});
