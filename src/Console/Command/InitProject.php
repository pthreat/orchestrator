<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Console\Command;

use Pthreat\Orchestrator\Config\OrchestratorConfig;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorException;
use Pthreat\Orchestrator\Utility\Fs;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitProject extends Command
{
    private const NAME = 'orchestrator:init';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function configure() : void
    {
        $this->setDescription('Initializes orchestrator project');
        $this->setDefinition(
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
        $projectDir = realpath(Fs::mkPath($input->getArgument('project-dir')));

        if(false === $projectDir){
            $output->writeln('<error>Invalid project directory</error>');
            return self::FAILURE;
        }

        try {
            $output->writeln('<info>Initializing project ...</info>');
            $output->writeln(
                sprintf(
                    '<info>Saved configuration in: %s</info>',
                    OrchestratorConfig::init($projectDir)->getFile()
                )
            );

            return self::SUCCESS;
        }catch(OrchestratorException $e){
            $output->writeln("<error>Failed to initialize orchestrator: {$e->getMessage()}</error>");
            return self::FAILURE;
        }
    }
}
