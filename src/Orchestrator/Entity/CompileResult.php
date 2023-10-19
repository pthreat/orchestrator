<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Orchestrator\Entity;

readonly class CompileResult
{

    public function __construct(
        private BuildResult $buildResult
    ){}

    public function getBuildResult() : BuildResult
    {
        return $this->buildResult;
    }

}
