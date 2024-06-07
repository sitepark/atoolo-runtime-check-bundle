<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

use InvalidArgumentException;

class CheckStatus
{
    private array $messages = [];

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

    public function addResult(string $scope, array $result): self
    {
        if (isset($this->results[$scope])) {
            throw new InvalidArgumentException("Scope $scope already exists");
        }
        $this->results[$scope] = $result;
        return $this;
    }

    public function getResults(): array
    {
        return $this->results;
    }

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

    public static function deserialize(array $data): CheckStatus
    {
        $status = new self($data['success']);
        foreach ($data as $name => $value) {
            if ($name === 'success') {
                continue;
            }
            if ($name === 'messages') {
                foreach ($value as $scope => $message) {
                    foreach ($message as $msg) {
                        $status->addMessage($scope, $msg);
                    }
                }
            } else {
                $status->addResult($name, $value);
            }
        }
        return $status;
    }
}
