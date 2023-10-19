<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Exception\Helper;

use Pthreat\Orchestrator\Traits\IterableTrait;

class ExceptionCollection implements ExceptionCollectionInterface
{
    use IterableTrait;

    public function __construct(iterable $exceptions)
    {
        /**
         * @var \Throwable $e
         */
        foreach ($exceptions as $e) {
            $this->add($e);
        }
    }

    private function add(\Throwable $e): void
    {
        $this->items[] = $e;
    }

    public static function fromException(\Throwable $e): ExceptionCollectionInterface
    {
        $ret = new self([]);
        do {
            $ret->add($e);
        } while ($e = $e->getPrevious());

        return $ret;
    }

    public function findException(string $class): \Throwable|null
    {
        if (!is_a($class, \Throwable::class, true)) {
            throw new \InvalidArgumentException('Passed class must be an instance of \Throwable');
        }

        /**
         * @var \Throwable $e
         */
        foreach ($this as $e) {
            if ($e instanceof $class) {
                return $e;
            }
        }

        return null;
    }

    public function getMessages(bool $verbose = false): array
    {
        $ret = [];

        /**
         * @var \Throwable $e
         */
        foreach ($this as $e) {
            $msg = "{$e->getMessage()}:{$e->getLine()}";

            if ($verbose) {
                $msg = "Message: {$e->getMessage()}, File: {$e->getFile()}, Line: {$e->getLine()}";
            }

            $ret[get_class($e)] = $msg;
        }

        return $ret;
    }

    public function getTraceAsArray(): array
    {
        $return = [];

        /**
         * @var \Exception $item
         */
        foreach ($this->items as $item) {
            $return[] = [
                'exception' => [
                    'class' => get_class($item),
                    'message' => $item->getMessage(),
                    'line' => $item->getLine(),
                    'file' => $item->getFile(),
                ],
                'trace' => $item->getTrace(),
            ];
        }

        return $return;
    }

    public function hasException(string $class): bool
    {
        return null !== self::findException($class);
    }

    public function toString(bool $verbose = true): string
    {
        $ret = [];
        foreach ($this->getMessages($verbose) as $class => $message) {
            $ret[] = $verbose ? sprintf('%s', $message) : sprintf('%s: %s', $class, $message);
        }

        return implode("\n", $ret);
    }

    public function __toString(): string
    {
        return $this->toString(false);
    }
}
