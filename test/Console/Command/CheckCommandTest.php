<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Console\Command;

use Atoolo\Runtime\Check\Console\Command\CheckCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CheckCommand::class)]
class CheckCommandTest extends TestCase
{
    private CommandTester $commandTester;

    public function setUp(): void
    {
        $command = new CheckCommand();

        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        $this->commandTester->execute([]);
    }
}
