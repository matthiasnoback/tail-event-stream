<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use TailEventStream\Stream;

sleep(2);
Stream::produce('hello_world', 'Hello, world!');