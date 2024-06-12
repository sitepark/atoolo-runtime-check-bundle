<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Requests\PostRequest;
use JsonException;
use Throwable;

class FastCGIStatus
{
    /**
     * @param string $socket 127.0.0.1:9000 or /var/run/php8.3-fpm.sock
     */
    public function __construct(
        private readonly string $socket,
        private readonly Client $client,
        private readonly ConfiguresSocketConnection $connection,
        private readonly string $frontControllerPath,
        //private readonly JWTTokenManagerInterface $jwtManager
    ) {
    }

    /**
     * @internal For testing purposes only
     * @codeCoverageIgnore
     */
    public function getSocket(): string
    {
        return $this->socket;
    }

    /**
     * @internal For testing purposes only
     * @codeCoverageIgnore
     */
    public function getConnection(): ConfiguresSocketConnection
    {
        return $this->connection;
    }

    public function request(string $content): CheckStatus
    {
        $request = new PostRequest($this->frontControllerPath, $content);
        $request->setRequestUri('/api/runtime-check');
        $request->setRemoteAddress('127.0.0.1');

        try {
            $res =  $this->client->sendRequest(
                $this->connection,
                $request
            );
            /** @var array<string, mixed> $data */
            $data = json_decode(
                $res->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            return CheckStatus::deserialize($data);
        } catch (JsonException $e) {
            return CheckStatus::createFailure()
                ->addMessage(
                    'fpm-fcgi',
                    sprintf(
                        'JSON error: %s',
                        $e->getMessage()
                    )
                );
        } catch (Throwable $e) {
            return CheckStatus::createFailure()
                ->addMessage(
                    'fpm-fcgi',
                    sprintf(
                        'FastCGI error: %s (%s)',
                        $e->getMessage(),
                        $this->socket
                    )
                );
        }
    }
}
