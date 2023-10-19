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

class WhereIsTaggedDefinition extends Command
{
    private const NAME = 'debug:tagged';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function configure() : void
    {
        $this->setDescription('Locates which services are tagged with a certain TAG')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument(
                        'tag',
                        InputArgument::REQUIRED,
                        'Tag name',
                    ),
                    new InputOption(
                        'regex',
                        'r',
                        InputOption::VALUE_NONE,
                        'Expression to search is a regex'
                    ),
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

            $regex = $input->getOption('regex');
            $tagName = $regex ? sprintf('#%s#i', $input->getArgument('tag')) : $input->getArgument('tag');

            $table = ConsoleHelper::tableHelperFactory(
                $output,
                null,
                [
                    'Service',
                    'Tag Name' .
                    'File',
                ]
            );

            $found = false;

            foreach ($orchestrator->findServiceFiles() as $file) {
                $dom = new \DOMDocument('1.0', 'utf8');
                $dom->load($file->getRealPath());
                $services = $dom->getElementsByTagName('service');
                $amount = $services->count();

                for ($i = 0; $i < $amount; ++$i) {
                    /**
                     * @var \DOMElement $item
                     */
                    $item = $services->item($i);
                    $serviceId = $item->getAttribute('id');
                    $tags = $item->getElementsByTagName('tag');

                    $tagCount = $tags->count();

                    for ($x = 0; $x < $tagCount; ++$x) {
                        /**
                         * @var \DOMElement $tag
                         */
                        $tag = $tags->item($x);
                        $name = $tag->getAttribute('name');

                        if (($regex && preg_match($tagName, $name)) || $name === $tagName) {
                            $found = true;
                            $table->addRow([$serviceId, $name, $file]);
                        }
                    }
                }
            }

            if (!$found) {
                $output->writeln("<error>Could not find any services tagged \"$tagName\"</error>");

                return self::FAILURE;
            }

            $table->render();

            $output->writeln("\n");

            return self::SUCCESS;
        } catch (OrchestratorException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return self::FAILURE;
        }
    }
}
