<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Test\Service;

use Atoolo\Runtime\Check\Service\CheckStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CheckStatus::class)]
class CheckStatusTest extends TestCase
{
    public function testCreateSuccess(): void
    {
        $status = CheckStatus::createSuccess();
        self::assertTrue($status->success, 'Status is not successful');
    }

    public function testCreateFailure(): void
    {
        $status = CheckStatus::createFailure();
        self::assertFalse($status->success, 'Status is not a failure');
    }

    public function testAddMessage(): void
    {
        $status = new CheckStatus(true);
        $status->addMessage('scope', 'message');
        self::assertSame(
            [
                'scope' => ['message']
            ],
            $status->getMessages(),
            'Message was not added correctly'
        );
    }

    public function testAddResult(): void
    {
        $status = new CheckStatus(true);
        $status->addResult('scope', ['result']);
        self::assertSame(
            [
                'scope' => ['result']
            ],
            $status->getResults(),
            'Result was not added correctly'
        );
    }

    public function testAddResultWithScopeAlreadyExists(): void
    {
        $status = new CheckStatus(true);
        $status->addResult('scope', ['result']);

        $this->expectExceptionMessage('Scope scope already exists');
        $status->addResult('scope', ['result']);
    }

    public function testApply(): void
    {
        $status = new CheckStatus(true);
        $toApply = CheckStatus::createFailure();
        $toApply->addResult('scope', ['result']);
        $status->apply($toApply);

        $expected = new CheckStatus(false);
        $expected->addResult('scope', ['result']);
        self::assertEquals(
            $expected,
            $status,
            'Status was not applied correctly'
        );
    }

    public function testSerialize(): void
    {
        $status = new CheckStatus(true);
        $status->addMessage('scope', 'message');
        $status->addResult('scope', ['result']);
        $serialized = $status->serialize();
        self::assertSame(
            [
                'success' => true,
                'scope' => ['result'],
                'messages' => [
                    'scope' => ['message']
                ],
            ],
            $serialized,
            'Status was not serialized correctly'
        );
    }

    public function testDeserialize(): void
    {
        $serialized = [
            'success' => true,
            'scope' => ['result'],
            'messages' => [
                'scope' => ['message']
            ],
        ];
        $status = CheckStatus::deserialize($serialized);
        $expected = new CheckStatus(true);
        $expected->addMessage('scope', 'message');
        $expected->addResult('scope', ['result']);

        self::assertEquals(
            $expected,
            $status,
            'Status was not deserialized correctly'
        );
    }
}
