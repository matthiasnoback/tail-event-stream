<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Assert\Assertion;
use TailEventStream\Producer;

$streamFilePath = getenv('STREAM_FILE_PATH');
Assertion::string($streamFilePath);

sleep(2);
$producer = new Producer($streamFilePath);
$producer->produce('hello_world', ['Hello' => 'World!']);
