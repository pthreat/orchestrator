<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Console\Command;

use Pthreat\Orchestrator\Console\ConsoleHelper;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorException;
use Pthreat\Orchestrator\Orchestrator\Orchestrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WhereIsContainerDefinition extends Command
{
    private const NAME = 'debug:where';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function configure() : void
    {
        $this->setDescription('Locates in which files a service ID is in use')
            ->setDefinition(
            new InputDefinition([
                new InputArgument(
                    'id',
                    InputArgument::REQUIRED,
                    'Service id',
                ),
                new InputOption(
                    'regex',
                    'r',
                    InputOption::VALUE_NONE,
                    'Expression to search is a regex'
                ),
            ])
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $orchestrator = Orchestrator::factory();

            $files = $orchestrator->findServiceFiles();
            $regex = $input->getOption('regex');
            $serviceId = $regex ? sprintf('#%s#i', $input->getArgument('id')) : $input->getArgument('id');

            $table = ConsoleHelper::tableHelperFactory(
                $output,
                null,
                [
                    'Matches',
                    'File',
                ]
            );

            $found = false;

            foreach ($files as $file) {
                $dom = new \DOMDocument('1.0', 'utf8');
                $dom->load($file->getRealPath());
                $services = $dom->getElementsByTagName('service');
                $amount = $services->count();

                for ($i = 0; $i < $amount; ++$i) {
                    /**
                     * @var \DOMElement $item
                     */
                    $item = $services->item($i);
                    $id = $item->getAttribute('id');

                    if (($regex && preg_match($serviceId, $id)) || $id === $serviceId) {
                        $found = true;
                        $table->addRow([$id, $file]);
                    }
                }
            }

            if (!$found) {
                $output->writeln("<error>Service matching $serviceId could not be found</error>");

                return self::FAILURE;
            }

            $table->render();

            $output->writeln("\n");

            return self::SUCCESS;
        }catch(OrchestratorException $e){
            $output->writeln("<error>{$e->getMessage()}</error>");

            return self::FAILURE;
        }
    }
}
