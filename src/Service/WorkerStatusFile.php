<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

use DateTime;
use JsonException;
use Symfony\Contracts\Service\ServiceProviderInterface;

class WorkerStatusFile
{
    public function __construct(
        private readonly string $workerStatusFile,
        public readonly int $updatePeriodInMinutes,
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

        $result = json_decode(
            file_get_contents($this->workerStatusFile),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $allowedTime =
            time() -
            (($this->updatePeriodInMinutes - $toleranceInMinutes) * 60);
        $lastRun = strtotime($result['last-run']);

        if ($lastRun < $allowedTime) {
            return CheckStatus::createFailure()
                ->addResult('worker', $result)
                ->addMessage(
                    'worker',
                    'The worker did not run in the last '
                    . $this->updatePeriodInMinutes
                    . ' minutes. Last run: ' . $result['last-run']
                );
        }

        return CheckStatus::createSuccess()
            ->addResult('worker', $result);
    }

    /**
     * @throws JsonException
     */
    public function write(ProcessStatus $phpStatus): void
    {
        $formatter = new DateTime();
        $results = $phpStatus->getStatus();
        $results['last-run'] = $formatter->format('d.m.Y H:i:s');
        file_put_contents(
            $this->workerStatusFile,
            json_encode($results, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }
}
