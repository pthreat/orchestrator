<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Config\Entity;


readonly class DirectoryConfig implements \JsonSerializable
{
    public function __construct(
        private array $included,
        private array $excluded,
        private bool $ignoreUnreadable
    ){}

    public function getIncluded(): array
    {
        return $this->included;
    }

    public function getExcluded(): array
    {
        return $this->excluded;
    }

    public function isIgnoreUnreadable(): bool
    {
        return $this->ignoreUnreadable;
    }

    public static function fromArray(array $array) : DirectoryConfig
    {
        return new self(
            $array['included'],
            $array['excluded'],
            $array['ignore_unreadable']
        );
    }

    public function toArray() : array
    {
        return [
            'included' => $this->included,
            'excluded' => $this->excluded,
            'ignore_unreadable' => $this->ignoreUnreadable
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}


