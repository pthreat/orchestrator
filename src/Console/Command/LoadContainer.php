<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Console\Command;

use Pthreat\Orchestrator\Console\Helper\OrchestratorPrintHelper;
use Pthreat\Orchestrator\Exception\Helper\ExceptionCollection;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorException;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorServiceReadException;
use Pthreat\Orchestrator\Orchestrator\Orchestrator;
use Pthreat\Orchestrator\Utility\Fs;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadContainer extends Command
{
    private const NAME = 'orchestrator:load';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function configure() : void
    {
        $this->setDescription('Loads container');

    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = Orchestrator::load();
            return self::SUCCESS;
        }catch(OrchestratorException $e){
            $output->writeln("<error>Failed to build container: {$e->getMessage()}</error>");
            $output->writeln(ExceptionCollection::fromException($e));
            return self::FAILURE;
        }
    }
}
