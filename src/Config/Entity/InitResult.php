<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Config\Entity;

use Pthreat\Orchestrator\Config\OrchestratorConfig;

readonly class InitResult
{
    public function __construct(
        private string $file,
        private OrchestratorConfig $config
    ){}

    public function getFile() : string
    {
        return $this->file;
    }

    public function getConfig() : OrchestratorConfig
    {
        return $this->config;
    }
}
