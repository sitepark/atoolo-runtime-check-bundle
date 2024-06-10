<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Service;

use Atoolo\Runtime\Check\Service\Platform;
use Atoolo\Runtime\Check\Service\ProcessStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

function fpm_get_status(): array
{
    return [];
}

#[CoversClass(ProcessStatus::class)]
class ProcessStatusTest extends TestCase
{
    private string $resourceDir = __DIR__
        . '/../resources/Service/ProcessStatusTest';

    private Platform $platform;

    public function setUp(): void
    {
        $this->platform = $this->createStub(Platform::class);
        $this->platform->method('getPhpIniLoadedFile')
            ->willReturn('php.ini');
        $this->platform->method('getIni')
            ->willReturnMap([
                ['date.timezone', 'Timezone']
            ]);
        $this->platform->method('getVersion')
            ->willReturn('8.3.0');
        $this->platform->method('getOpcacheGetStatus')
            ->willReturn([
                'memory_usage' => '123',
                'other' => 'test'
            ]);
        $this->platform->method('getFpmPoolStatus')
            ->willReturn([
                'pool' => 'www',
                'other' => 'test'
            ]);
    }

    public function testGetStatus(): void
    {
        $processStatus = new ProcessStatus(
            $this->resourceDir . '/processStatus.json',
            'fpm-fcgi',
            $this->platform
        );
        $status = $processStatus->getStatus();

        $this->assertEquals(
            [
                'user' => '',
                'group' => '',
                'php' => [
                    'version' => '8.3.0',
                    'ini' => [
                        'file' => 'php.ini',
                        'date.timezone' => 'Timezone'
                    ],
                    'fpm' => [
                        'config' => [
                            'section' => [
                                'key' => 'value',
                                'includetest' => 'test'
                            ],
                            'global' => [
                                'include' => 'fpm/conf.d/*.conf'
                            ]
                        ],
                        'status' => [
                            'pool' => 'www'
                        ]
                    ],
                    'opcache' => [
                        'memory_usage' => '123'
                    ]
                ]
            ],
            $status,
            'Unexpected status'
        );
    }

    public function testGetStatusWithEmptyConfig(): void
    {
        $processStatus = new ProcessStatus(
            $this->resourceDir . '/empty-processStatus.json',
            'fpm-fcgi',
            $this->platform
        );
        $status = $processStatus->getStatus();
        $this->assertEquals(
            [
                'user' => '',
                'group' => '',
                'php' => [
                    'version' => '8.3.0',
                    'ini' => [
                        'file' => 'php.ini',
                    ],
                    'fpm' => [
                        'config' => [
                        ],
                        'status' => [
                        ]
                    ],
                    'opcache' => [
                    ]
                ]
            ],
            $status,
            'Unexpected status'
        );
    }
}
