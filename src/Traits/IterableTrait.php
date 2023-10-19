<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Traits;

trait IterableTrait
{
    private array $items = [];
    private int $count = 0;

    public function rewind(): void
    {
        reset($this->items);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    #[\ReturnTypeWillChange]
    public function first(): mixed
    {
        return reset($this->items);
    }

    #[\ReturnTypeWillChange]
    public function last(): mixed
    {
        return end($this->items);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->items);
    }

    public function valid(): bool
    {
        $key = key($this->items);

        return null !== $key;
    }

    public function count(): int
    {
        if ($this->count > 0) {
            return $this->count;
        }

        return $this->count = count($this->items);
    }

    public function filter(callable $callback, ...$args): self
    {
        return new static(array_filter($this->__items(), $callback, \ARRAY_FILTER_USE_BOTH), ...$args);
    }

    public function slice(int $length, int|null $offset, ...$args): self
    {
        /* The order of arguments is NOT a mistake */
        return new static(array_slice($this->__items(), (int) $offset, $length, true), ...$args);
    }

    public function map(callable $callback): mixed
    {
        return array_map(
            $callback,
            $this->__items()
        );
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function reduce(callable $callback, $initial = null): mixed
    {
        return array_reduce($this->__items(), $callback, $initial);
    }

    public function sort(callable $callback): self
    {
        $items = $this->__items();
        uasort($items, $callback);

        return new static($items);
    }

    // <editor-fold desc="Private methods">
    private function __items(): array
    {
        return $this->items;
    }
    // <editor-fold>
}
