<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Config\Entity;

readonly class ContainerConfig implements \JsonSerializable
{

    public function __construct(
        private string $namespace,
        private string $class
    ){}

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public static function fromArray(array $array) : ContainerConfig
    {
        return new self(
            $array['namespace'],
            $array['class']
        );
    }

    public function toArray() : array
    {
        return [
            'namespace' => $this->namespace,
            'class' => $this->class
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
