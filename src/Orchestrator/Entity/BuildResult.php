<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Orchestrator\Entity;

use Symfony\Component\DependencyInjection\ContainerBuilder;

readonly class BuildResult
{

    public function __construct(
        private ContainerBuilder $builder,
        private array $files,
        private array $compilerPasses
    ){}

    public function getContainerBuilder() : ContainerBuilder
    {
        return $this->builder;
    }

    /**
     * @return \SplFileInfo[]
     */
    public function getServiceFiles() : array
    {
        return $this->files;
    }

    /**
     * @return \SplFileInfo[]
     */
    public function getCompilerPasses() : array
    {
        return $this->compilerPasses;
    }

    public function getCountCompilerPasses() : int
    {
        return count($this->compilerPasses);
    }

    public function getCountServiceFiles() : int
    {
        return count($this->files);
    }

}
