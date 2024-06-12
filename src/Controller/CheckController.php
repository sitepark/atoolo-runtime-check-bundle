<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Controller;

use Atoolo\Runtime\Check\Service\CheckStatus;
use Atoolo\Runtime\Check\Service\CliStatus;
use Atoolo\Runtime\Check\Service\ProcessStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CheckController extends AbstractController
{
    public function __construct(
        private readonly ProcessStatus $processStatus,
        private readonly CliStatus $cliStatus,
        private readonly string $scope = PHP_SAPI
    ) {
    }

    #[Route('/api/runtime-check', name: 'atoolo_runtime_check')]
    #[IsGranted(
        attribute: new Expression(
            'is_granted("ROLE_SYSTEM_AUDITOR") '
            . 'or "127.0.0.1" == subject.getClientIp()'
        ),
        subject: new Expression('request')
    )]
    public function check(Request $request): Response
    {
        $status = CheckStatus::createSuccess();
        $status->addReport($this->scope, array_merge(
            [
                'host' => $_SERVER['SERVER_NAME']
            ],
            $this->processStatus->getStatus()
        ));

        $skip = $request->get('skip') ?? [];

        if (!in_array('cli', $skip, true)) {
            $status->apply($this->cliStatus->execute());
        }
        $res = new JsonResponse($status->serialize());
        $res->setStatusCode($status->success ? 200 : 500);
        return $res;
    }
}
