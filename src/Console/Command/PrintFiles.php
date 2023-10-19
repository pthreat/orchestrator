<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Console\Command;

use Pthreat\Orchestrator\Console\Helper\OrchestratorPrintHelper;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorException;
use Pthreat\Orchestrator\Orchestrator\Orchestrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrintFiles extends Command
{
    private const NAME = 'debug:files';

    public function __construct()
    {
        parent::__construct(self::NAME);
        $this->setDescription('Prints service file locations');
    }

    public function configure() : void
    {
        $this->setDescription('Print available service files')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        'project-dir',
                        InputArgument::OPTIONAL,
                        'Project directory',
                        getcwd()
                    )
                ])
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $orchestrator = Orchestrator::factory();

            OrchestratorPrintHelper::printFiles($output, $orchestrator->build());

            return self::SUCCESS;
        }catch(OrchestratorException $e){
            $output->writeln("<error>{$e->getMessage()}</error>");

            return self::FAILURE;
        }
    }
}
