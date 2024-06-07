<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

use Exception;
use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use JsonException;
use Throwable;

class FastCGIStatus
{
    protected Client $client;

    protected NetworkSocket
        |UnixDomainSocket
        |ConfiguresSocketConnection $connection;

    /**
     * @var array<string> of patterns matching php socket files
     */
    protected $possibleSocketFilePatterns = [
        '/var/run/php-fpm.sock',
        '/var/run/php/php-fpm.sock',
        '/var/run/php*.sock',
        '/var/run/php/*.sock'
    ];

    protected string $socket;

    /**
     * @param string $socket 127.0.0.1:9000 or /var/run/php5-fpm.sock
     */
    public function __construct(string $socket = null)
    {
        // try to guess where it is
        if ($socket === null) {
            foreach (
                $this->possibleSocketFilePatterns as $possibleSocketFilePattern
            ) {
                $possibleSocketFile = current(glob($possibleSocketFilePattern));
                if (
                    $possibleSocketFile !== false
                    && file_exists($possibleSocketFile)
                ) {
                    $socket = $possibleSocketFile;
                    break;
                }
            }
            if ($socket === null) {
                $socket = '127.0.0.1:9000';
            }
        }

        $this->socket = $socket;

        if (str_contains($socket, ':')) {
            $last = strrpos($socket, ':');
            $port = substr($socket, $last + 1, strlen($socket));
            $host = substr($socket, 0, $last);

            $IPv6 = '/^(?:[A-F0-9]{0,4}:){1,7}[A-F0-9]{0,4}$/';
            if (preg_match($IPv6, $socket) === 1) {
                // IPv6 addresses need to be surrounded by brackets
                // see: https://www.php.net/manual/en/function.stream-socket-client.php#refsect1-function.stream-socket-client-notes
                $socket = "[{$socket}]";
            }

            $this->connection = new NetworkSocket(
                $host,
                (int)$port,
                5000,     # Connect timeout in milliseconds (default: 5000)
                120000    # Read/write timeout in milliseconds (default: 5000)
            );
        } else {
            $this->connection = new UnixDomainSocket(
                $socket,
                5000,   # Connect timeout in milliseconds (default: 5000)
                120000  # Read/write timeout in milliseconds (default: 5000)
            );
        }

        $this->client = new Client();
    }

    public function request(string $scriptPath, string $content): CheckStatus
    {
        $request = new PostRequest($scriptPath, $content);
        $request->setRequestUri('/runtime-check');

        try {
            $res =  $this->client->sendRequest(
                $this->connection,
                $request
            );
            return CheckStatus::deserialize(json_decode(
                $res->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            ));
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
