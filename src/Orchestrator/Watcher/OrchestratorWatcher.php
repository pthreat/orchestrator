<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Orchestrator\Watcher;

use Pthreat\Orchestrator\Console\Command\PrintFiles;
use Pthreat\Orchestrator\Console\Helper\OrchestratorPrintHelper;
use Pthreat\Orchestrator\Exception\Helper\ExceptionCollection;
use Pthreat\Orchestrator\Orchestrator\Orchestrator;
use Pthreat\Orchestrator\Orchestrator\Watcher\Config\OrchestratorWatcherConfig;
use Pthreat\Orchestrator\Orchestrator\Watcher\Exception\OrchestratorWatcherException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class OrchestratorWatcher
{

    private bool $stopped=false;

    private function __construct(
        private Orchestrator $orchestrator
    ){}

    public static function factory(Orchestrator $orchestrator) : OrchestratorWatcher
    {
        return new self($orchestrator);
    }

    /**
     * @throws OrchestratorWatcherException
     */
    public function watch(InputInterface $input, OutputInterface $output) : void
    {
        try {

            $output->writeln('<info>Orchestrator file watcher starting ...</info>');

            $config = $this->orchestrator->getConfig()->getWatcherConfig();
            $excludedDirectories = array_merge(
                $config->getDirectories()->getExcluded(),
                [$this->orchestrator->getConfig()->getContainerWriteConfig()->getDirectory()]
            );

            $fd = inotify_init();

            $finder = new Finder();

            $directories = $finder->exclude($excludedDirectories)
                ->ignoreUnreadableDirs($config->getDirectories()->isIgnoreUnreadable())
                ->in($config->getDirectories()->getIncluded());

            $watches = [];

            foreach($directories as $path){
                $wd = inotify_add_watch($fd, $path->getRealPath(), IN_MODIFY | IN_ATTRIB | IN_MOVE | IN_CREATE | IN_DELETE | IN_DONT_FOLLOW);
                $watches[$wd] = $path;
            }

            $read = [$fd];
            $write = null;
            $except = null;
            stream_select($read, $write, $except, 0);
            stream_set_blocking($fd, false);

            while (!$this->stopped) {
                while ($events = inotify_read($fd)) {
                    $action = '';
                    $file = '';
                    foreach($events as $details){
                        $file = $watches[$details['wd']];

                        if ($details['mask'] & IN_CREATE) {
                            $action = OrchestratorWatcherConfig::FILE_CREATED;
                            break;
                        }

                        if (
                            ($details['mask'] & IN_MODIFY || $details['mask'] & IN_ATTRIB) ||
                            ($details['mask'] & IN_MOVED_TO || $details['mask'] & IN_MOVED_FROM)
                        ){
                            $action = OrchestratorWatcherConfig::FILE_UPDATED;
                            break;
                        }

                        if ($details['mask'] & IN_DELETE) {
                            $action = OrchestratorWatcherConfig::FILE_DELETED;
                            break;
                        }
                    }

                    if('' !== $action) {
                        $output->writeln("<info>$file: $action</info>");
                    }

                    try {
                        $result = $this->orchestrator->compile();
                        $output->writeln('<info>Container built successfully</info>');

                        if($input->getOption('verbose')) {
                            OrchestratorPrintHelper::printFiles($output, $result->getBuildResult());
                        }
                    }catch(\Throwable $e){
                        $output->writeln('<error>Error building container</error>');
                        $output->writeln($e->getMessage());
                        $output->writeln("\n\n<fg=yellow>Stack trace:</>");
                        $output->writeln(ExceptionCollection::fromException($e));
                    }
                    usleep(100*1000);
                }
            }

        }catch(\Throwable $e){
            throw new Exception\OrchestratorWatcherException('Could not start watcher', 0, $e);
        }
    }


}
