<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Exception\Helper;

interface ExceptionCollectionInterface extends \Countable, \Iterator
{
    /**
     * Factory method.
     */
    public static function fromException(\Throwable $e): ExceptionCollectionInterface;

    /**
     * @throws \InvalidArgumentException if passed class is not an instance of \Throwable
     */
    public function findException(string $class): \Throwable|null;

    /**
     * @throws \InvalidArgumentException if passed class is not an instance of \Throwable
     */
    public function hasException(string $class): bool;

    public function getTraceAsArray(): array;

    /**
     * Returns an array of exception messages were the key is the exception class and the value is the message
     * of said exception.
     *
     * If $verbose is true, file and line will also be part of the message
     *
     * @return string[]
     */
    public function getMessages(bool $verbose): array;

    public function toString(bool $verbose): string;

    public function __toString(): string;
}
