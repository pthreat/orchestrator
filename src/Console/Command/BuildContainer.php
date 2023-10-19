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

class BuildContainer extends Command
{
    private const NAME = 'orchestrator:build';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function configure() : void
    {
        $this->setDescription('Builds container');

    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectDir = getcwd();
        $outputDir = Fs::mkPath($projectDir,'cache');
        $envFile = Fs::mkPath($projectDir,'.env');

        try {
            $start = microtime(true);
            $result = Orchestrator::factory()
                ->compile($outputDir, $envFile);

            if ($input->getOption('verbose')) {
                OrchestratorPrintHelper::printFiles($output, $result->getBuildResult());
            }

            OrchestratorPrintHelper::printSummary($output, $result->getBuildResult(), $start);

            return self::SUCCESS;
        }catch(OrchestratorServiceReadException $e){
            $output->writeln('<error>Read error while trying to find service files</error>');
            $output->writeln(ExceptionCollection::fromException($e));
        }catch(OrchestratorException $e){
            $output->writeln("<error>Failed to build container: {$e->getMessage()}</error>");
            $output->writeln(ExceptionCollection::fromException($e));
            return self::FAILURE;
        }
    }
}
