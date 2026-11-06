<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Service\FpmFcgi;

use Atoolo\Runtime\Check\Service\FpmFcgi\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(AccessToken::class)]
class AccessTokenTest extends TestCase
{
    public function testCreateIsDeterministic(): void
    {
        $this->assertSame(
            (new AccessToken('secret'))->create(),
            (new AccessToken('secret'))->create(),
            'token should be stable for the same secret',
        );
    }

    public function testCreateDependsOnSecret(): void
    {
        $this->assertNotSame(
            (new AccessToken('secret-a'))->create(),
            (new AccessToken('secret-b'))->create(),
            'token should differ for different secrets',
        );
    }

    public function testCreateWithEmptySecret(): void
    {
        $this->assertSame('', (new AccessToken(''))->create());
    }

    public function testIsValid(): void
    {
        $accessToken = new AccessToken('secret');
        $request = Request::create('/', server: [
            AccessToken::SERVER_VAR => $accessToken->create(),
        ]);
        $this->assertTrue($accessToken->isValid($request));
    }

    public function testIsValidWithWrongToken(): void
    {
        $accessToken = new AccessToken('secret');
        $request = Request::create('/', server: [
            AccessToken::SERVER_VAR => (new AccessToken('other'))->create(),
        ]);
        $this->assertFalse($accessToken->isValid($request));
    }

    public function testIsValidWithoutToken(): void
    {
        $accessToken = new AccessToken('secret');
        $this->assertFalse($accessToken->isValid(Request::create('/')));
    }

    public function testIsValidWithHttpHeader(): void
    {
        $accessToken = new AccessToken('secret');
        $request = Request::create('/', server: [
            'HTTP_' . AccessToken::SERVER_VAR => $accessToken->create(),
        ]);
        $this->assertFalse(
            $accessToken->isValid($request),
            'token sent as HTTP header must not be accepted',
        );
    }

    public function testIsValidWithEmptySecret(): void
    {
        $accessToken = new AccessToken('');
        $request = Request::create('/', server: [
            AccessToken::SERVER_VAR => '',
        ]);
        $this->assertFalse($accessToken->isValid($request));
    }
}
