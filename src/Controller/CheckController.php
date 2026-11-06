<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Controller;

use Atoolo\Runtime\Check\Service\FpmFcgi\AccessToken;
use Atoolo\Runtime\Check\Service\FpmFcgi\RuntimeCheck;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CheckController extends AbstractController
{
    public function __construct(
        private readonly RuntimeCheck $runtimeCheck,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly AccessToken $accessToken,
    ) {}

    /**
     * @throws JsonException
     */
    #[Route('/api/runtime-check', name: 'atoolo_runtime_check')]
    public function check(Request $request): Response
    {
        $this->denyUnlessAllowed($request);

        $skip = $request->get('skip') ?? [];
        if (is_string($skip)) {
            $skip = explode(',', $skip);
        } elseif (!is_array($skip)) {
            $skip = [];
        }

        /** @var array<string> $skip */
        $runtimeStatus = $this->runtimeCheck->execute($skip);

        $result = $runtimeStatus->serialize();
        $success = $runtimeStatus->isSuccess();

        $res = new JsonResponse($result);
        $res->setStatusCode($success ? 200 : 500);
        return $res;
    }

    /**
     * Access is granted to users with ROLE_SYSTEM_AUDITOR and to the
     * `runtime:check` console command, which sends the access token as
     * FastCGI parameter. The client IP is deliberately not used: behind
     * a reverse proxy on the same host every request comes from 127.0.0.1.
     */
    private function denyUnlessAllowed(Request $request): void
    {
        if ($this->authorizationChecker->isGranted('ROLE_SYSTEM_AUDITOR')) {
            return;
        }
        if ($this->accessToken->isValid($request)) {
            return;
        }
        throw new AccessDeniedException('Access Denied.');
    }
}
