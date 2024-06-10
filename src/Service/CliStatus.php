<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

use Exception;
use Symfony\Component\Process\Process;

class CliStatus
{
    public function __construct(
        private string $consoleBinPath
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function execute(): CheckStatus
    {
        $process = new Process([
            $this->consoleBinPath,
            'runtime:check',
            '--fpm-skip',
            '--json'
        ]);
        try {
            $process->run();
        // @codeCoverageIgnoreStart
        // found no way to test this
        } catch (Exception $e) {
            return CheckStatus::createFailure()
                ->addMessage('cli', trim($e->getMessage()));
        }
        // @codeCoverageIgnoreEnd

        if (!$process->isSuccessful()) {
            return CheckStatus::createFailure()
                ->addMessage('cli', trim($process->getErrorOutput()));
        }

        /** @var array<string, mixed> $data */
        $data = json_decode(
            $process->getOutput(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        return CheckStatus::deserialize($data);
    }
}
