<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Controller;

use Atoolo\Runtime\Check\Service\CheckStatus;
use Atoolo\Runtime\Check\Service\ProcessStatus;
use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

final class CheckController extends AbstractController
{
    public function __construct(
        private readonly ProcessStatus $phpStatus
    ) {
    }
    /**
     */
    #[Route('/runtime-check', name: 'atoolo_runtime_check')]
    public function check(Request $request): Response
    {
        $status = CheckStatus::createSuccess();
        $status->addResult(PHP_SAPI, array_merge(
            [
                'host' => $_SERVER['SERVER_NAME'],
            ],
            $this->phpStatus->getStatus()
        ));

        if (!$request->get('cli-skip', false)) {
            $status->apply($this->executeCheckCommand());
        }
        $res = new JsonResponse($status->serialize());
        $res->setStatusCode($status->success ? 200 : 500);
        return $res;
    }

    /**
     * @throws JsonException
     */
    private function executeCheckCommand(): CheckStatus
    {
        $process = new Process([
            $this->getConsoleBinPath(),
            'runtime:check',
            '--fpm-skip',
            '--json'
        ]);
        try {
            $process->run();
        } catch (Exception $e) {
            return CheckStatus::createFailure()
                ->addMessage('cli', trim($e->getMessage()));
        }
        if (!$process->isSuccessful()) {
            return CheckStatus::createFailure()
                ->addMessage('cli', trim($process->getErrorOutput()));
        }

        return CheckStatus::deserialize(json_decode(
            $process->getOutput(),
            true,
            512,
            JSON_THROW_ON_ERROR
        ));
    }

    private function getConsoleBinPath(): string
    {
        return $this->getParameter('kernel.project_dir') . '/bin/console';
    }
}
