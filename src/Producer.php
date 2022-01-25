<?php
declare(strict_types=1);

namespace TailEventStream;

use RuntimeException;

final class Producer
{
    private string $streamFilePath;

    public function __construct(string $streamFilePath)
    {
        $this->streamFilePath = $streamFilePath;
        if (!is_file($this->streamFilePath)) {
            touch($this->streamFilePath);
        }
    }

    /**
     * @param string $messageType
     * @param array<string,mixed> $data
     */
    public function produce(string $messageType, array $data): void
    {
        $encodedMessage = json_encode([
            'messageType' => $messageType,
            'data' => $data
        ]);

        $result = file_put_contents($this->streamFilePath, $encodedMessage . "\n", FILE_APPEND);

        if ($result === false) {
            throw new RuntimeException(
                sprintf('Could not append the message to the stream (path: %s)', $this->streamFilePath)
            );
        }
    }
}
