<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service\FpmFcgi;

use Symfony\Component\HttpFoundation\Request;

/**
 * Token that authorizes the `runtime:check` console command when it
 * requests the FPM status directly via FastCGI.
 *
 * The token is passed as a FastCGI parameter (not as an HTTP header).
 * A web server only forwards client headers as `HTTP_*` parameters, so
 * an HTTP client cannot set this parameter. The value is derived from
 * the kernel secret, which CLI and FPM of the same project share.
 */
class AccessToken
{
    public const SERVER_VAR = 'ATOOLO_RUNTIME_CHECK_TOKEN';

    private const HMAC_DATA = 'atoolo-runtime-check';

    public function __construct(
        private readonly string $secret,
    ) {}

    public function create(): string
    {
        if ($this->secret === '') {
            return '';
        }
        return hash_hmac('sha256', self::HMAC_DATA, $this->secret);
    }

    public function isValid(Request $request): bool
    {
        $expected = $this->create();
        if ($expected === '') {
            return false;
        }

        $token = $request->server->get(self::SERVER_VAR);
        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }
}
