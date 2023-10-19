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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\GraphvizDumper;
use Symfony\Component\DependencyInjection\Reference;

class DumpContainerGraph extends Command
{
    private const NAME = 'debug:graph';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function configure() : void
    {
        $this->setDescription('Generates a graphviz output file which can be rendered as a flow chart')
            ->setDefinition(
            new InputDefinition([
                new InputArgument('output',
                    InputArgument::REQUIRED,
                    'Output file name'
                ),
                new InputArgument(
                    'id',
                    InputArgument::OPTIONAL,
                    'Dumps a graphviz filtered only for the entered service id',
                )
            ])
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $serviceId = $input->getArgument('id');

        try {
            $orchestrator = Orchestrator::factory();

            $builder = $orchestrator->build();

            if($input->getOption('verbose')){
                OrchestratorPrintHelper::printFiles($output, $builder);
            }

            if ($serviceId) {
                try {
                    $definitions = $this->dumpOneService($serviceId, $builder->getContainerBuilder());
                    $builder = new ContainerBuilder();

                    $builder->addDefinitions($definitions);
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    $output->writeln("<error>Undefined service $serviceId</error>");

                    return self::FAILURE;
                }
            }

            file_put_contents(
                $input->getArgument('output'),
                (new GraphvizDumper($builder))->dump()
            );

            $output->writeln("<info>Output saved in: {$input->getArgument('output')}</info>");

            return self::SUCCESS;
        }catch(OrchestratorException $e){
            $output->writeln("<error>{$e->getMessage()}</error>");
            return self::FAILURE;
        }
    }

    public function dumpOneService(string $id, ContainerBuilder $builder): array
    {
        $definitions = [];

        try {
            $definition = $builder->getDefinition($id);
            $definitions[] = $definition;
        } catch (\Throwable $e) {
            return $definitions;
        }

        /**
         * @var Reference $ref
         */
        foreach ($definition->getArguments() as $ref) {
            if ($ref instanceof Reference) {
                foreach ($this->dumpOneService((string) $ref, $builder) as $def) {
                    $definitions[] = $def;
                }
            }
        }

        return $definitions;
    }
}
