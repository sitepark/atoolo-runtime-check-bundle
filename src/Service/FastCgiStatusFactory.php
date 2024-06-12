<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;

class FastCgiStatusFactory
{
    /**
     * @param array<string> $possibleSocketFilePatterns
     * of patterns matching php socket files
     */
    public function __construct(
        private readonly array $possibleSocketFilePatterns,
        private readonly string $frontControllerPath
    ) {
    }

    public function create(?string $socket = null): FastCGIStatus
    {
        $socket = $socket ?? $this->determineSocket();
        $client = new Client();
        $connection = $this->createConnection($socket);
        return new FastCGIStatus(
            $socket,
            $client,
            $connection,
            $this->frontControllerPath
        );
    }

    private function determineSocket(): string
    {
        foreach (
            $this->possibleSocketFilePatterns as $possibleSocketFilePattern
        ) {
            $files = glob($possibleSocketFilePattern) ?: [];
            $possibleSocketFile = current($files);
            if (
                $possibleSocketFile !== false
                && file_exists($possibleSocketFile)
            ) {
                return $possibleSocketFile;
            }
        }
        return '127.0.0.1:9000';
    }

    private function createConnection(
        string $socket
    ): ConfiguresSocketConnection {
        $last = strrpos($socket, ':');
        if ($last !== false) {
            $port = substr($socket, $last + 1, strlen($socket));
            $host = substr($socket, 0, $last);

            return new NetworkSocket(
                $host,
                (int)$port,
                5000,     # Connect timeout in milliseconds (default: 5000)
                120000    # Read/write timeout in milliseconds (default: 5000)
            );
        }

        return new UnixDomainSocket(
            $socket,
            5000,   # Connect timeout in milliseconds (default: 5000)
            120000  # Read/write timeout in milliseconds (default: 5000)
        );
    }
}