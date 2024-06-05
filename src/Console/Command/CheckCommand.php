<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Console\Command;

use Atoolo\Deployment\Service\Deployer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'runtime:check',
    description: 'Check the runtime environment',
)]
final class CheckCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        return Command::SUCCESS;
    }
}
