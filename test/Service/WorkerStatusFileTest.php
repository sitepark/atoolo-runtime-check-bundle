<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Service;

use Atoolo\Runtime\Check\Service\CheckStatus;
use Atoolo\Runtime\Check\Service\Platform;
use Atoolo\Runtime\Check\Service\ProcessStatus;
use Atoolo\Runtime\Check\Service\WorkerStatusFile;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkerStatusFile::class)]
class WorkerStatusFileTest extends TestCase
{
    private string $resourceDir = __DIR__
        . '/../resources/Service/WorkerStatusFileTest';

    private string $testDir = __DIR__
        . '/../../../var/test/WorkerStatusFileTest';

    public function setUp(): void
    {
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0777, true);
        }
    }

    public function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            foreach (scandir($this->testDir) as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                unlink($this->testDir . '/' . $file);
            }
            rmdir($this->testDir);
        }
    }

    public function testRead(): void
    {
        $date = new DateTime(
            '2024-06-10 13:31:00'
        );
        $platform = $this->createStub(Platform::class);
        $platform->method('time')
            ->willReturn($date->getTimestamp());

        $statusFile = new WorkerStatusFile(
            $this->resourceDir . '/statusFile.json',
            10,
            $platform
        );
        $status = $statusFile->read();

        $expected = CheckStatus::createSuccess();
        $expected->addReport('worker', [
            'last-run' => '10.06.2024 13:16:00'
        ]);

        $this->assertEquals(
            $expected,
            $status,
            'Unexpected status'
        );
    }

    public function testReadFileNotExists(): void
    {
        $statusFile = new WorkerStatusFile(
            $this->resourceDir . '/no-exists.json',
            10,
        );
        $status = $statusFile->read();

        $expected = CheckStatus::createFailure();
        $expected->addMessage('worker', 'worker not running');

        $this->assertEquals(
            $expected,
            $status,
            'Unexpected status'
        );
    }

    public function testReadFileLastRunExpired(): void
    {
        $date = new DateTime(
            '2024-06-10 13:32:00'
        );
        $platform = $this->createStub(Platform::class);
        $platform->method('time')
            ->willReturn($date->getTimestamp());

        $statusFile = new WorkerStatusFile(
            $this->resourceDir . '/statusFile.json',
            10,
            $platform
        );
        $status = $statusFile->read();

        $expected = CheckStatus::createFailure();
        $expected->addReport('worker', [
            'last-run' => '10.06.2024 13:16:00'
        ]);
        $expected->addMessage(
            'worker',
            'The worker did not run in the last 10 minutes.'
                . ' Last run: 10.06.2024 13:16:00'
        );

        $this->assertEquals(
            $expected,
            $status,
            'Unexpected status'
        );
    }

    public function testReadWithInvalidLastRun(): void
    {
        $date = new DateTime(
            '2024-06-10 13:16:00'
        );
        $platform = $this->createStub(Platform::class);
        $platform->method('time')
            ->willReturn($date->getTimestamp());

        $statusFile = new WorkerStatusFile(
            $this->resourceDir . '/statusFileWithInvalidLastRun.json',
            10,
            $platform
        );
        $status = $statusFile->read();

        $expected = CheckStatus::createFailure();
        $expected->addReport('worker', [
            'last-run' => 123
        ]);
        $expected->addMessage(
            'worker',
            'The worker did not run in the last 10 minutes.'
            . ' Last run: 123'
        );

        $this->assertEquals(
            $expected,
            $status,
            'Unexpected status'
        );
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testWrite(): void
    {

        $date = new DateTime(
            '2024-06-10 13:32:00'
        );
        $platform = $this->createStub(Platform::class);
        $platform->method('time')
            ->willReturn($date->getTimestamp());

        $file = $this->testDir . '/statusFile.json';
        $statusFile = new WorkerStatusFile(
            $file,
            10,
            $platform
        );

        $processStatus = $this->createStub(ProcessStatus::class);
        $processStatus->method('getStatus')
            ->willReturn([
                'success' => true
            ]);

        $statusFile->write($processStatus);

        $writtenData = json_decode(
            file_get_contents($file),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertEquals(
            [
                'success' => true,
                'last-run' => '10.06.2024 13:32:00'
            ],
            $writtenData,
            'Unexpected status'
        );
    }
}
