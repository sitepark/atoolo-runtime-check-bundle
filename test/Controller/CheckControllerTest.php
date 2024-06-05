<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Controller;

use Atoolo\Runtime\Check\Controller\CheckController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(CheckController::class)]
class CheckControllerTest extends TestCase
{
    private CheckController $controller;

    public function setUp(): void
    {
        $this->controller = new CheckController();
    }

    public function testCheck(): void
    {
        $request = $this->createMock(Request::class);
        $this->controller->deploy($request);
    }
}
