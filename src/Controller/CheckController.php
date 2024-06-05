<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CheckController extends AbstractController
{
    /**
     */
    #[Route('/api/admin/runtime-check', name: 'atoolo_runtime_check')]
    public function deploy(Request $request): Response
    {
        return new JsonResponse(['success' => true]);
    }
}
