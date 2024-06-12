<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Service;

use Atoolo\Runtime\Check\Service\ProcessStatus;
use Atoolo\Runtime\Check\Service\WorkerCheckEvent;
use Atoolo\Runtime\Check\Service\WorkerCheckScheduler;
use Atoolo\Runtime\Check\Service\WorkerStatusFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;

#[CoversClass(WorkerCheckScheduler::class)]
class WorkerCheckSchedulerTest extends TestCase
{
    public function testGetSchedule(): void
    {
        $workerStatusFile = new WorkerStatusFile('', 10);
        $processStatus = $this->createStub(ProcessStatus::class);
        $lockFactory = $this->createStub(LockFactory::class);
        $workerCheckScheduler = new WorkerCheckScheduler(
            $workerStatusFile,
            $processStatus,
            $lockFactory
        );

        $schedule = $workerCheckScheduler->getSchedule();

        $this->assertEquals(
            2,
            count($schedule->getRecurringMessages())
        );
    }

    public function testInvoke(): void
    {
        $workerStatusFile = $this->createMock(WorkerStatusFile::class);
        $processStatus = $this->createStub(ProcessStatus::class);
        $lockFactory = $this->createStub(LockFactory::class);
        $workerCheckScheduler = new WorkerCheckScheduler(
            $workerStatusFile,
            $processStatus,
            $lockFactory
        );

        $workerStatusFile->expects($this->once())
            ->method('write')
            ->with($processStatus);
        $workerCheckScheduler->__invoke(new WorkerCheckEvent());
    }
}
