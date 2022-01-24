<?php
declare(strict_types=1);

namespace TailEventStream;

use stdClass;

final class Producer
{
    private string $streamFilePath;

    public function __construct(string $streamFilePath)
    {
        $this->streamFilePath = $streamFilePath;
    }

    /**
     * @param string $messageType
     * @param mixed $data
     */
    public function produce(string $messageType, $data): void
    {
        $message = new stdClass();
        $message->messageType = $messageType;
        $message->data = $data;

        $encodedMessage = json_encode($message);

        file_put_contents($this->streamFilePath, $encodedMessage . "\n", FILE_APPEND);
    }
}
