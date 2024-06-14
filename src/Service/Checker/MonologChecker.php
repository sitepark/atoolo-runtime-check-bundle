<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service\Checker;

use Atoolo\Runtime\Check\Service\CheckStatus;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class MonologChecker implements Checker
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function getScope(): string
    {
        return 'logging';
    }

    public function check(): CheckStatus
    {
        if (!($this->logger instanceof Logger)) {
            $status = CheckStatus::createFailure();
            $status->addMessage(
                $this->getScope(),
                'unknown: unsupported logger ' . get_class($this->logger)
            );
            return $status;
        }

        $handler = $this->getStreamHandler($this->logger);
        if ($handler === null) {
            $status = CheckStatus::createFailure();
            $status->addMessage(
                $this->getScope(),
                'unknown: no stream handler found'
            );
            return $status;
        }

        $file = $handler->getUrl();
        if ($file === null) {
            return $this->createFailure(
                $handler,
                'logfile not set'
            );
        }
        if (file_exists($file) && !is_writable($file)) {
            return $this->createFailure(
                $handler,
                'logfile not writable: ' . $file
            );
        }

        if (!file_exists($file)) {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                    return $this->createFailure(
                        $handler,
                        'log directory cannot be created: ' . $dir
                    );
                }
            }

            if (!@touch($file)) {
                return $this->createFailure(
                    $handler,
                    'logfile cannot be created: ' . $file
                );
            }
        }

        $status = CheckStatus::createSuccess();
        $status->addReport($this->getScope(), [
            'logfile' => $handler->getUrl(),
            'level' => $handler->getLevel()->getName(),
        ]);

        return $status;
    }

    public function createFailure(
        StreamHandler $handler,
        string $message
    ): CheckStatus {
        $status = CheckStatus::createFailure();
        $status->addReport($this->getScope(), [
            'logfile' => $handler->getUrl(),
            'level' => $handler->getLevel()->getName(),
        ]);
        $status->addMessage($this->getScope(), $message);
        return $status;
    }

    private function getStreamHandler(
        Logger $logger
    ): ?StreamHandler {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof StreamHandler) {
                return $handler;
            }
        }
        return null;
    }
}
