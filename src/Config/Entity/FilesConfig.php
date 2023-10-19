<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Config\Entity;

readonly class FilesConfig implements \JsonSerializable
{
    public function __construct(
        private array $patterns,
        private array $extensions,
        private bool $ignoreUnreadable
    ){}

    public static function fromArray(array $array) : FilesConfig
    {
        return new self(
            $array['patterns'],
            $array['extensions'],
            $array['ignore_unreadable']
        );
    }
    
    public function isIgnoreUnreadable() : bool
    {
        return $this->ignoreUnreadable;
    }

    public function getExtensions() : array
    {
        return $this->extensions;
    }

    public function getPatterns() : array
    {
        return $this->patterns;
    }

    public function toArray() : array
    {
        return [
            'patterns' => $this->patterns,
            'extensions' => $this->extensions,
            'ignore_unreadable' => $this->ignoreUnreadable
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}


