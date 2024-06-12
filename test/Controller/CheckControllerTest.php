<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Controller;

use Atoolo\Runtime\Check\Controller\CheckController;
use Atoolo\Runtime\Check\Service\CheckStatus;
use Atoolo\Runtime\Check\Service\CliStatus;
use Atoolo\Runtime\Check\Service\ProcessStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(CheckController::class)]
class CheckControllerTest extends TestCase
{
    private ProcessStatus $processStatus;

    private CliStatus $cliStatus;

    private string $originServerName;

    public function setUp(): void
    {
        $this->originServerName = $_SERVER['SERVER_NAME'] ?? '';
        $_SERVER['SERVER_NAME'] = 'www.example.com';

        $this->processStatus = $this->createStub(ProcessStatus::class);
        $this->cliStatus = $this->createStub(CliStatus::class);
        $this->controller = new CheckController(
            $this->processStatus,
            $this->cliStatus,
            'fpm-fcgi'
        );
    }

    public function tearDown(): void
    {
        $_SERVER['SERVER_NAME'] = $this->originServerName;
    }

    public function testCheck(): void
    {
        $this->processStatus->method('getStatus')->willReturn([
            'user' => 'test'
        ]);

        $cliStatus = CheckStatus::createSuccess();
        $cliStatus->addReport('cli', [
            'user' => 'test'
        ]);
        $this->cliStatus->method('execute')->willReturn(
            $cliStatus
        );

        $request = $this->createMock(Request::class);
        $response = $this->controller->check($request);
        $json = json_decode(
            $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $this->assertEquals(
            [
                'success' => true,
                'reports' => [
                    'cli' => [
                        'user' => 'test'
                    ],
                    'fpm-fcgi' => [
                        'host' => 'www.example.com',
                        'user' => 'test'
                    ]
                ],
                'messages' => [
                ],
            ],
            $json,
            'Unexpected response content'
        );
    }
}
