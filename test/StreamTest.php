<?php
declare(strict_types=1);

namespace TailEventStream\Test;

use Asynchronicity\PHPUnit\Eventually;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

final class StreamTest extends TestCase
{
    private string $streamFilePath;

    private Process $consumer;

    private Process $helloWorldProducer;

    private Process $fooBarProducer;

    protected function setUp(): void
    {
        $temporaryStreamFilePath = tempnam(sys_get_temp_dir(), 'stream_file_path');
        if (!is_string($temporaryStreamFilePath)) {
            $this->fail('Could not create a temporary stream file');
        }
        $this->streamFilePath = $temporaryStreamFilePath;

        $this->helloWorldProducer = new Process(
            [
                'php',
                'produce_hello_world.php'
            ],
            __DIR__,
            [
                'STREAM_FILE_PATH' => $this->streamFilePath
            ]
        );

        $this->fooBarProducer = new Process(
            [
                'php',
                'produce_foo_bar.php',
            ],
            __DIR__,
            [
                'STREAM_FILE_PATH' => $this->streamFilePath
            ]
        );
    }

    /**
     * @test
     */
    public function it_can_be_used_to_produce_and_consume_messages(): void
    {
        $this->consumer = new Process(
            [
                'php',
                'consume.php',
            ],
            __DIR__,
            [
                'STREAM_FILE_PATH' => $this->streamFilePath
            ]
        );

        $this->consumer->start();
        $this->helloWorldProducer->run();

        // give it some time, then check for startup errors
        sleep(1);
        if ($this->consumer->isTerminated()) {
            throw new RuntimeException('Consumer failed: ' . $this->consumer->getErrorOutput());
        }

        self::assertThat(
            function () {
                self::assertStringContainsString("'Hello' => 'World!'", $this->consumer->getOutput());
            },
            new Eventually(5000, 500),
            'Complete output: ' . $this->consumer->getOutput()
        );
    }

    /**
     * @test
     */
    public function it_can_consume_starting_with_a_given_index(): void
    {
        /*
         * A special consumer which starts from index 1 (meaning, the second message
         */
        $this->consumer = new Process(
            [
                'php',
                'consume_from_index_1.php',
            ],
            __DIR__,
            [
                'STREAM_FILE_PATH' => $this->streamFilePath
            ]
        );

        $this->fooBarProducer->run();
        $this->helloWorldProducer->run();
        $this->helloWorldProducer->run();
        $this->consumer->start();

        // give it some time, then check for startup errors
        sleep(1);
        if ($this->consumer->isTerminated()) {
            throw new RuntimeException('Consumer failed: ' . $this->consumer->getErrorOutput());
        }

        self::assertThat(
            function () {
                self::assertEquals("hello_world\nhello_world\n", $this->consumer->getOutput());
            },
            new Eventually(5000, 500),
            sprintf('Actual output was: "%s"', $this->consumer->getOutput())
        );
    }

    protected function tearDown(): void
    {
        $this->consumer->stop(0, SIGTERM);

        $this->helloWorldProducer->stop();

        $this->fooBarProducer->stop();

        @unlink($this->streamFilePath);
    }
}
