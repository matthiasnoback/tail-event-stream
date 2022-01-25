<?php
declare(strict_types=1);

namespace TailEventStream;

use Assert\Assertion;
use RuntimeException;
use Symfony\Component\Process\Process;

final class Consumer
{
    private string $streamFilePath;

    public function __construct(string $streamFilePath)
    {
        $this->streamFilePath = $streamFilePath;
        if (!is_file($this->streamFilePath)) {
            touch($this->streamFilePath);
        }
    }

    public function consume(callable $callback, int $startAtIndex = 0): void
    {
        Assertion::greaterOrEqualThan($startAtIndex, 0, 'The consumer can only start consuming at index 0 or greater');

        // read all of the stream at once, then keep following new additions
        $location = 1 + $startAtIndex;
        $process = new Process(
            [
                'tail',
                '-f',
                '-n',
                '+' . $location,
                $this->streamFilePath
            ]
        );

        // never stop
        $process->setTimeout(null);

        // don't forward output, let the callback deal with it
        $process->disableOutput();

        // stop the consumer when the parent process stops
        $stop = function () use ($process) {
            $process->stop(0);
        };
        register_shutdown_function($stop);
        pcntl_signal(SIGTERM, function () use ($stop) {
            $stop();
        });

        // start `tail`
        $process->start(function ($type, $data) use ($callback) {
            if ($type === Process::OUT) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    if ($line === '') {
                        continue;
                    }

                    $decodedMessage = json_decode($line, true);
                    Assertion::isArray($decodedMessage);
                    Assertion::keyExists($decodedMessage, 'messageType');
                    Assertion::keyExists($decodedMessage, 'data');

                    $callback($decodedMessage['messageType'], $decodedMessage['data']);
                }
            }

            if ($type === Process::ERR) {
                throw new RuntimeException('ERR: ' . $data);
            }
        });

        while ($process->isRunning()) {
            usleep(1000);
            pcntl_signal_dispatch();
        }
    }
}
