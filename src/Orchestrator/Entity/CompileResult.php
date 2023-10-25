<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Orchestrator\Entity;

readonly class CompileResult
{

    public function __construct(
        private BuildResult $buildResult,
        private float $time
    ){}

    public function getBuildResult() : BuildResult
    {
        return $this->buildResult;
    }

    public function getTime() : float
    {
        return $this->time;
    }

}
