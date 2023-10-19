<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Config\Entity;

readonly class EnvWriteConfig implements \JsonSerializable
{

    public function __construct(
        private string $directory,
        private string $file
    ){}

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getFile(): string
    {
        return $this->file;
    }


    public static function fromArray(array $array) : EnvWriteConfig
    {
        return new self(
            $array['directory'],
            $array['file']
        );
    }

    public function toArray() : array
    {
        return [
            'directory' => $this->directory,
            'file' => $this->file
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
