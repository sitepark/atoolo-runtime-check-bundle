<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

use InvalidArgumentException;

class CheckStatus
{
    /**
     * @var array<string, array<string>>
     */
    private array $messages = [];

    /**
     * @var array<string,array<string,mixed>>
     */
    private array $results = [];

    public function __construct(public bool $success)
    {
    }

    public static function createSuccess(): self
    {
        return new self(true);
    }

    public static function createFailure(): self
    {
        return new self(false);
    }

    public function addMessage(string $scope, string $message): self
    {
        if (!isset($this->messages[$scope])) {
            $this->messages[$scope] = [];
        }
        $this->messages[$scope][] = $message;
        return $this;
    }

    /**
     * @param array<string,mixed> $result
     */
    public function addResult(string $scope, array $result): self
    {
        if (isset($this->results[$scope])) {
            throw new InvalidArgumentException("Scope $scope already exists");
        }
        $this->results[$scope] = $result;
        return $this;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @return array<string,array<string>>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function apply(CheckStatus $status): self
    {
        $this->success = $this->success && $status->success;
        $this->messages = array_merge_recursive(
            $this->messages,
            $status->messages
        );
        foreach ($status->results as $scope => $result) {
            $this->addResult($scope, $result);
        }
        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function serialize(): array
    {
        return array_merge(
            [
                'success' => $this->success
            ],
            $this->results,
            [
                'messages' => $this->messages,
            ]
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function deserialize(array $data): CheckStatus
    {
        $success = is_bool($data['success']) ? $data['success'] : false;

        $status = new self($success);
        foreach ($data as $name => $value) {
            if ($name === 'success') {
                continue;
            }
            if ($name === 'messages') {
                /** @var array<string,array<string>> $value */
                foreach ($value as $scope => $message) {
                    foreach ($message as $msg) {
                        $status->addMessage($scope, $msg);
                    }
                }
            } else {
                /** @var array<string,array<mixed>> $value */
                $status->addResult($name, $value);
            }
        }
        return $status;
    }
}
