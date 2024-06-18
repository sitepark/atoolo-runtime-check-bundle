<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service\Checker;

use Atoolo\Runtime\Check\Service\CheckStatus;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FingersCrossedHandler;
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

        $handlers = $this->getStreamHandlers($this->logger);
        if (empty($handlers)) {
            $status = CheckStatus::createFailure();
            $status->addMessage(
                $this->getScope(),
                'unknown: no stream handler found'
            );
            return $status;
        }

        $status = CheckStatus::createSuccess();
        foreach ($handlers as $handler) {
            $status = $this->checkHandler($status, $handler);
        }
        return $status;
    }

    private function checkHandler(
        CheckStatus $mergedStatus,
        StreamHandler $handler
    ): CheckStatus {
        $file = $handler->getUrl();
        if ($file === null) {
            return $this->createFailure(
                $mergedStatus,
                $handler,
                'logfile not set'
            );
        }
        if (file_exists($file) && !is_writable($file)) {
            return $this->createFailure(
                $mergedStatus,
                $handler,
                'logfile not writable: ' . $file
            );
        }

        if (!file_exists($file)) {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                    return $this->createFailure(
                        $mergedStatus,
                        $handler,
                        'log directory cannot be created: ' . $dir
                    );
                }
            }

            if (!@touch($file)) {
                return $this->createFailure(
                    $mergedStatus,
                    $handler,
                    'logfile cannot be created: ' . $file
                );
            }
        }

        return $this->createSuccess(
            $mergedStatus,
            $handler
        );
    }

    private function createFailure(
        CheckStatus $mergedStatus,
        StreamHandler $handler,
        string $message
    ): CheckStatus {
        $status = $mergedStatus->apply(CheckStatus::createFailure());
        $status->addMessage($this->getScope(), $message);
        return $this->applyStatusReport($status, $handler);
    }

    private function createSuccess(
        CheckStatus $mergedStatus,
        StreamHandler $handler,
    ): CheckStatus {
        $status = $mergedStatus->apply(CheckStatus::createSuccess());
        return $this->applyStatusReport($status, $handler);
    }

    private function applyStatusReport(
        CheckStatus $status,
        StreamHandler $handler
    ): CheckStatus {
        $report = $status->getReport($this->getScope());
        $report['handler'][] = [
            'logfile' => $handler->getUrl(),
            'level' => $handler->getLevel()->getName(),
        ];
        $status->replaceReport($this->getScope(), $report);
        return $status;
    }

    /**
     * @param Logger $logger
     * @return array<StreamHandler>
     */
    private function getStreamHandlers(
        Logger $logger
    ): array {
        $handlers = [];
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FingersCrossedHandler) {
                $nestedHandler = $handler->getHandler();
                if ($nestedHandler instanceof StreamHandler) {
                    $handlers[] = $nestedHandler;
                }
            } elseif ($handler instanceof StreamHandler) {
                $handlers[] = $handler;
            }
        }
        return $handlers;
    }
}
