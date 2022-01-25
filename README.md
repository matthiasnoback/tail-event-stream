# TailEventStream

An event stream library based on `tail`.

## Getting started

Install using Composer:

```
composer require matthiasnoback/tail-event-stream 
```

## Usage

Adding messages to the stream:

```php
use TailEventStream\Producer;

$streamFilePath = __DIR__ . '/var/stream.txt';

$producer = new Producer($streamFilePath);
$producer->produce('hello_world', ['Hello' => 'World!']);
```

The `stream.txt` file contains one message per line:

```json
{"messageType":"hello_world","data":{"Hello":"World!"}}
```

Using `tail -f` a consumer can read each message from the stream, and it will keep consuming messages until you quit the process:

```php
use TailEventStream\Consumer;

$streamFilePath = __DIR__ . '/var/stream.txt';

$consumer = new Consumer($streamFilePath);

$consumer->consume(function (string $messageType, array $data) {
    // $messageType will be 'hello_world'
    // $data will  be ['Hello' => 'World!']
});
```

`consume()` accepts a second argument, which is the index (or line) at which to start.
