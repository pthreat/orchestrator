<?php declare(strict_types=1);

namespace Pthreat\Orchestrator\Console\Helper;

use Pthreat\Orchestrator\Orchestrator\Entity\BuildResult;
use Symfony\Component\Console\Output\OutputInterface;

readonly class OrchestratorPrintHelper
{

    public static function printFiles(OutputInterface $output, BuildResult $result) : void
    {
        $output->writeln('<info>Service files</info>');
        $output->writeln(str_repeat('-',80));
        $output->writeln("\n");

        foreach($result->getServiceFiles() as $file){
            $output->writeln("<fg=bright-blue>{$file->getRealPath()}</>");
        }

        $output->writeln("\n");

        $output->writeln('<info>Compiler passes</info>');
        $output->writeln(str_repeat('-',80));
        $output->writeln("\n");

        foreach($result->getCompilerPasses() as $file){
            $output->writeln("<fg=bright-blue>{$file->getRealPath()}</>");
        }

        $output->writeln("\n");
    }

    public static function printSummary(OutputInterface $output, BuildResult $result, float $start) : void
    {
        $output->writeln(sprintf('<info>Found %s service files</info>', $result->getCountServiceFiles()));
        $output->writeln(sprintf('<info>Found %s compiler passes</info>', $result->getCountCompilerPasses()));
        $output->writeln(sprintf('<info>Time: %s ms</info>', number_format(microtime(true) - $start, 2)));
    }

}
