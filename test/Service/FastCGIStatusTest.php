<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Service;

use Atoolo\Runtime\Check\Service\CheckStatus;
use Atoolo\Runtime\Check\Service\FastCGIStatus;
use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(FastCGIStatus::class)]
class FastCGIStatusTest extends TestCase
{
    private Client $client;
    private ConfiguresSocketConnection $connection;
    private FastCGIStatus $fastCGIStatus;
    private ProvidesResponseData $response;

    public function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->connection = $this->createMock(
            ConfiguresSocketConnection::class
        );
        $this->response = $this->createStub(ProvidesResponseData::class);

        $this->fastCGIStatus = new FastCGIStatus(
            'mysocket',
            $this->client,
            $this->connection,
            '/path/to/front-controller'
        );
    }

    public function testRequest(): void
    {
        $this->response->method('getBody')
            ->willReturn(json_encode([
                'success' => true,
                'reports' => [
                    'scope' => [
                        'a' => 'b'
                    ]
                ]
            ], JSON_THROW_ON_ERROR));
        $this->client->method('sendRequest')
            ->willReturn($this->response);


        $status = $this->fastCGIStatus->request('');

        $expected = CheckStatus::createSuccess();
        $expected->addReport('scope', [
            'a' => 'b'
        ]);

        $this->assertEquals(
            $expected,
            $status,
            'Unexpected CheckStatus'
        );
    }

    public function testRequestWithInvalidJson(): void
    {
        $this->response->method('getBody')
            ->willReturn('invalid-json');
        $this->client->method('sendRequest')
            ->willReturn($this->response);

        $status = $this->fastCGIStatus->request('');

        $expected = CheckStatus::createFailure();
        $expected->addMessage(
            'fpm-fcgi',
            'JSON error: Syntax error'
        );

        $this->assertEquals(
            $expected,
            $status,
            'Unexpected CheckStatus'
        );
    }

    public function testRequestWithException(): void
    {
        $this->client->method('sendRequest')
            ->willThrowException(new RuntimeException('error'));

        $status = $this->fastCGIStatus->request('');

        $expected = CheckStatus::createFailure();
        $expected->addMessage(
            'fpm-fcgi',
            'FastCGI error: error (mysocket)'
        );

        $this->assertEquals(
            $expected,
            $status,
            'Unexpected CheckStatus'
        );
    }
}
