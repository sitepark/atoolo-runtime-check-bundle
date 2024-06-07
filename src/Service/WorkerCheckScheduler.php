<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

use JsonException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule(name: 'atoolo-runtime-check')]
#[AsMessageHandler]
class WorkerCheckScheduler implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function __construct(
        private readonly WorkerStatusFile $workerStatusFile,
        private readonly ProcessStatus $phpStatus,
        private readonly LockFactory $lockFactory = new LockFactory(
            new SemaphoreStore()
        ),
    ) {
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->add(
                RecurringMessage::every(
                    $this->workerStatusFile->updatePeriodInMinutes . ' seconds',
                    new WorkerCheckEvent()
                ),
            )->lock($this->lockFactory->createLock(
                'runtime-check-scheduler'
            ));
    }

    /**
     * @throws JsonException
     */
    public function __invoke(WorkerCheckEvent $message): void
    {
        $this->workerStatusFile->write($this->phpStatus);
    }
}
