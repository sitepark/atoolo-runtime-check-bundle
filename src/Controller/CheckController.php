<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Controller;

use Atoolo\Runtime\Check\Service\CheckStatus;
use Atoolo\Runtime\Check\Service\CliStatus;
use Atoolo\Runtime\Check\Service\ProcessStatus;
use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

final class CheckController extends AbstractController
{
    public function __construct(
        private readonly ProcessStatus $processStatus,
        private readonly CliStatus $cliStatus,
        private readonly string $scope = PHP_SAPI
    ) {
    }
    /**
     */
    #[Route('/runtime-check', name: 'atoolo_runtime_check')]
    public function check(Request $request): Response
    {
        $status = CheckStatus::createSuccess();
        $status->addResult($this->scope, array_merge(
            [
                'host' => $_SERVER['SERVER_NAME'],
            ],
            $this->processStatus->getStatus()
        ));

        if (!$request->get('cli-skip', false)) {
            $status->apply($this->cliStatus->execute());
        }
        $res = new JsonResponse($status->serialize());
        $res->setStatusCode($status->success ? 200 : 500);
        return $res;
    }
}
