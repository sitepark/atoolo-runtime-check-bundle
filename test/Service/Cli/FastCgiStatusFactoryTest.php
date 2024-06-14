<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Service\Cli;

use Atoolo\Runtime\Check\Service\Cli\FastCgiStatusFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FastCgiStatusFactory::class)]
class FastCgiStatusFactoryTest extends TestCase
{
    private string $resourceDir = __DIR__
    . '/../../resources/Service/Cli/FastCgiStatusFactoryTest';

    public function testCreateWithDefaultSocket(): void
    {
        $factory = new FastCgiStatusFactory(
            possibleSocketFilePatterns: [],
            frontControllerPath: '',
            resourceRoot: '',
            resourceHost: ''
        );
        $status = $factory->create();

        $this->assertEquals(
            '127.0.0.1:9000',
            $status->getSocket(),
            'Unexpected socket'
        );
    }

    public function testCreateWithGivenSocket(): void
    {
        $factory = new FastCgiStatusFactory(
            possibleSocketFilePatterns: [],
            frontControllerPath: '',
            resourceRoot: '',
            resourceHost: ''
        );
        $status = $factory->create('1.2.3.4:9000');

        $this->assertEquals(
            '1.2.3.4:9000',
            $status->getSocket(),
            'Unexpected socket'
        );
    }

    public function testCreateWithUnixSocket(): void
    {
        $factory = new FastCgiStatusFactory(
            possibleSocketFilePatterns: [
                $this->resourceDir . '/unix-*'
            ],
            frontControllerPath: '',
            resourceRoot: '',
            resourceHost: ''
        );

        $status = $factory->create();

        $this->assertEquals(
            $this->resourceDir . '/unix-socket',
            $status->getSocket(),
            'Unexpected socket'
        );
    }
}
