<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Console\Command;

use Atoolo\Runtime\Check\Console\Command\Io\TypifiedInput;
use Atoolo\Runtime\Check\Service\CheckStatus;
use Atoolo\Runtime\Check\Service\FastCgiStatusFactory;
use Atoolo\Runtime\Check\Service\ProcessStatus;
use Atoolo\Runtime\Check\Service\WorkerStatusFile;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'runtime:check',
    description: 'Check the runtime environment',
)]
final class CheckCommand extends Command
{
    public function __construct(
        private readonly FastCgiStatusFactory $fastCgiStatusFactory,
        private readonly WorkerStatusFile $workerStatusFile,
        private readonly ProcessStatus $processStatus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to performs a check of the runtime environment')
            ->addArgument(
                'skip-fpm',
                InputArgument::OPTIONAL,
                'Skip check for fpm.'
            )
            ->addOption(
                'fpm-skip',
                null,
                InputOption::VALUE_NEGATABLE,
                'Skip check for fpm.',
                false
            )
            ->addOption(
                'fpm-socket',
                null,
                InputOption::VALUE_OPTIONAL,
                'fpm FastCGI socket like 127.0.0.1:9000 or '
                . '/var/run/php/php8.3-fpm.sock'
            )
            ->addOption(
                'json',
                null,
                InputOption::VALUE_NEGATABLE,
                'output result in json.',
                false
            )
        ;
    }

    /**
     * @throws JsonException
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $typedInput = new TypifiedInput($input);

        $status = CheckStatus::createSuccess();
        $status->addResult(PHP_SAPI, array_merge(
            [
                'script' => $_SERVER['SCRIPT_FILENAME'] ?? 'n/a',
            ],
            $this->processStatus->getStatus()
        ));

        $status->apply($this->workerStatusFile->read());

        if (!$typedInput->getBoolOption('fpm-skip')) {
            $fastCgi = $this->fastCgiStatusFactory->create(
                $typedInput->getStringOption('fpm-socket')
            );
            $content = http_build_query(['cli-skip' => true]);
            $status->apply($fastCgi->request($content));
        }
        $this->outputResults(
            $output,
            $status,
            $typedInput->getBoolOption('json')
        );

        return $status->success
            || $typedInput->getBoolOption('json')
                ? Command::SUCCESS
                : Command::FAILURE;
    }

    /**
     * @throws JsonException
     */
    private function outputResults(
        OutputInterface $output,
        CheckStatus $status,
        bool $json
    ): void {
        if ($json) {
            $output->writeln(
                json_encode(
                    $status->serialize(),
                    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
                )
            );
        } else {
            foreach ($status->getResults() as $scope => $value) {
                $value = json_encode(
                    $value,
                    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
                );
                $output->writeln('<info>'  . $scope . '</info>');
                $output->writeln($value);
                $output->writeln('');
            }
            if ($status->success) {
                $output->writeln('<info>Success</info>');
            } else {
                $output->writeln('<error>Failure</error>');
                foreach ($status->getMessages() as $scope => $messages) {
                    foreach ($messages as $message) {
                        $output->writeln(
                            '<error>' . $scope . ': ' . $message . '</error>'
                        );
                    }
                }
            }
        }
    }
}
