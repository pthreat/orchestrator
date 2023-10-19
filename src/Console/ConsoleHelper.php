<?php
declare(strict_types=1);

namespace Pthreat\Orchestrator\Console;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ConsoleHelper
{
    public static function tableHelperFactory(
        OutputInterface $output,
        string $title = null,
        array $headers = []
    ): Table {
        $tableStyle = new TableStyle();
        $tableStyle->setHeaderTitleFormat('<fg=white;bg=blue;options=bold;> %s </>');
        $tableStyle->setHorizontalBorderChars('─');
        $tableStyle->setCrossingChars(
            '─',
            '┌',
            '┬',
            '┐',
            '┤',
            '┘',
            '┴',
            '└',
            '├',
            '├',
            '┼',
            '┤'
        );

        $table = new Table($output);

        if (null !== $title) {
            $table->setHeaderTitle($title);
        }

        if (count($headers) > 0) {
            $table->setHeaders($headers);
        }

        $table->setStyle($tableStyle);

        return $table;
    }
    public static function progressBarFactory(OutputInterface $output, int $total): ProgressBar
    {
        $progressBar = new ProgressBar($output, $total);
        $progressBar->setBarCharacter('▩');
        $progressBar->setEmptyBarCharacter('▢');
        $progressBar->setProgressCharacter('▶');
        $progressBar->setOverwrite(true);

        return $progressBar;
    }

    public function clearScreen(OutputInterface $output): void
    {
        $output->write("\033\143");
    }

    public function ask(InputInterface $input, OutputInterface $output, string $question): string|null
    {
        return (new SymfonyQuestionHelper())
            ->ask(
                $input,
                $output,
                new Question($question)
            );
    }
}