<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Console\Command;

use Atoolo\Runtime\Check\Console\Command\CheckCommand;
use Atoolo\Runtime\Check\Service\CheckStatus;
use Atoolo\Runtime\Check\Service\FastCGIStatus;
use Atoolo\Runtime\Check\Service\FastCgiStatusFactory;
use Atoolo\Runtime\Check\Service\ProcessStatus;
use Atoolo\Runtime\Check\Service\WorkerStatusFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CheckCommand::class)]
class CheckCommandTest extends TestCase
{
    private CommandTester $commandTester;

    private ProcessStatus $processStatus;

    private FastCGIStatus $fastCgiStatus;

    private string $originScriptFilename;

    public function setUp(): void
    {
        $this->originScriptFilename = $_SERVER['SCRIPT_FILENAME'];
        $_SERVER['SCRIPT_FILENAME'] = '/test/bin/console';

        $fastCgiStatusFactory = $this->createStub(
            FastCgiStatusFactory::class
        );
        $this->fastCgiStatus = $this->createStub(
            FastCGIStatus::class
        );
        $fastCgiStatusFactory->method('create')->willReturn(
            $this->fastCgiStatus
        );
        $workerStatusFile = $this->createStub(
            WorkerStatusFile::class
        );
        $workerStatusFile->method('read')->willReturn(
            CheckStatus::createSuccess()
        );
        $this->processStatus = $this->createStub(
            ProcessStatus::class
        );

        $command = new CheckCommand(
            $fastCgiStatusFactory,
            $workerStatusFile,
            $this->processStatus
        );

        $this->commandTester = new CommandTester($command);
    }

    public function tearDown(): void
    {
        $_SERVER['SCRIPT_FILENAME'] = $this->originScriptFilename;
    }

    public function testExecuteSuccess(): void
    {
        $this->fastCgiStatus->method('request')->willReturn(
            CheckStatus::createSuccess()
        );
        $this->commandTester->execute([]);
        $this->assertStringContainsString(
            'Success',
            $this->commandTester->getDisplay(),
            'Command should display success message'
        );
    }

    public function testExecuteFailure(): void
    {
        $status = CheckStatus::createFailure();
        $status->addMessage('test', 'test message');
        $this->fastCgiStatus->method('request')->willReturn(
            $status
        );
        $this->commandTester->execute([]);
        $this->assertEquals(
            <<<EOF
cli
{
    "script": "\/test\/bin\/console"
}

Failure
test: test message

EOF,
            $this->commandTester->getDisplay(),
            'Command should display failure message'
        );
    }

    public function testJsonResult(): void
    {
        $this->fastCgiStatus->method('request')->willReturn(
            CheckStatus::createSuccess()
        );
        $this->processStatus->method('getStatus')->willReturn(
            [
                'user' => 'user',
            ]
        );

        $this->commandTester->execute(
            ['--json' => true]
        );
        $this->assertEquals(
            <<<EOF
{
    "success": true,
    "cli": {
        "script": "\/test\/bin\/console",
        "user": "user"
    },
    "messages": []
}

EOF,
            $this->commandTester->getDisplay(),
            'Command should display json result'
        );
    }
}
