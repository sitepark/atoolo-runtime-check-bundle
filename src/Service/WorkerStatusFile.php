<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

use DateTime;
use JsonException;

class WorkerStatusFile
{
    public function __construct(
        private readonly string $workerStatusFile,
        public readonly int $updatePeriodInMinutes,
        private readonly Platform $platform = new Platform()
    ) {
    }

    /**
     * @throws JsonException
     */
    public function read(): CheckStatus
    {
        if (!file_exists($this->workerStatusFile)) {
            return CheckStatus::createFailure()
                ->addMessage('worker', 'worker not running');
        }

        $toleranceInMinutes = $this->updatePeriodInMinutes / 2;

        /** @var array<string,mixed> $result */
        $result = json_decode(
            file_get_contents($this->workerStatusFile) ?: '{}',
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $allowedTime =
            $this->platform->time() -
            (($this->updatePeriodInMinutes + $toleranceInMinutes) * 60);
        $lastRun = is_string($result['last-run'])
            ? strtotime($result['last-run'])
            : 0;

        if ($lastRun < $allowedTime) {
            return CheckStatus::createFailure()
                ->addReport('worker', $result)
                ->addMessage(
                    'worker',
                    'The worker did not run in the last '
                    . $this->updatePeriodInMinutes
                    . ' minutes. Last run: ' . $result['last-run']
                );
        }

        return CheckStatus::createSuccess()
            ->addReport('worker', $result);
    }

    /**
     * @throws JsonException
     */
    public function write(ProcessStatus $phpStatus): void
    {
        $now = new DateTime();
        $now->setTimestamp($this->platform->time()); // testable
        $results = $phpStatus->getStatus();
        $results['last-run'] = $now->format('d.m.Y H:i:s');
        file_put_contents(
            $this->workerStatusFile,
            json_encode($results, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }
}
