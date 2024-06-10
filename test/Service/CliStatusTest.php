<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Service;

use Atoolo\Runtime\Check\Service\CliStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CliStatus::class)]
class CliStatusTest extends TestCase
{
    private string $resourceDir = __DIR__
        . '/../resources/Service/CliStatusTest';
    public function testExecuteSuccess(): void
    {
        $consoleBinPath = $this->resourceDir . '/console-success';
        $cliStatus = new CliStatus($consoleBinPath);
        $checkStatus = $cliStatus->execute();
        $this->assertTrue(
            $checkStatus->success,
            'CheckStatus should be successful'
        );
    }

    public function testExecuteWithExitCode1(): void
    {
        $consoleBinPath = $this->resourceDir . '/console-exitcode-1';
        $cliStatus = new CliStatus($consoleBinPath);
        $checkStatus = $cliStatus->execute();
        $this->assertFalse(
            $checkStatus->success,
            'CheckStatus should be successful'
        );
    }
}
