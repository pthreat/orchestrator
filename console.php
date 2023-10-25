#!/usr/bin/env php
<?php

declare(strict_types=1);

use Pthreat\Orchestrator\Console\Command\LoadContainer;
use Pthreat\Orchestrator\Console\Command\WatchFSChanges;
use Symfony\Component\Console\Application;
use Pthreat\Orchestrator\Console\Command\BuildContainer;
use Pthreat\Orchestrator\Console\Command\PrintFiles;
use Pthreat\Orchestrator\Console\Command\WhereIsContainerDefinition;
use Pthreat\Orchestrator\Console\Command\WhereIsTaggedDefinition;
use Pthreat\Orchestrator\Console\Command\InitProject;

define('ORCHESTRATOR_COMPOSER_AUTOLOAD', implode(\DIRECTORY_SEPARATOR, [__DIR__,'vendor','autoload.php']));
define('PROJECT_COMPOSER_AUTOLOAD', implode(\DIRECTORY_SEPARATOR, [getcwd(),'vendor','autoload.php']));

if ('cli' !== PHP_SAPI) {
    echo "This script must be run from the command line\n";
    exit(1);
}

if(!file_exists(ORCHESTRATOR_COMPOSER_AUTOLOAD)){
	echo "Please run: composer install\n";
    exit(1);
}

require ORCHESTRATOR_COMPOSER_AUTOLOAD;

if(!file_exists(PROJECT_COMPOSER_AUTOLOAD)){
    echo "Please run: composer init\n";
}

require PROJECT_COMPOSER_AUTOLOAD;

try {
    $app = new Application('Orchestrator', '1.0');

    $app->addCommands([
            new InitProject(),
            new BuildContainer(),
            new LoadContainer(),
            new PrintFiles(),
            new WhereIsContainerDefinition(),
            new WhereIsTaggedDefinition(),
            new WatchFSChanges()
    ]);

    $app->run();
}catch(\Throwable $e){
    echo $e->getMessage();
}
