<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Orchestrator\Watcher\Config;

use Pthreat\Orchestrator\Config\Entity\DirectoryConfig;

readonly class OrchestratorWatcherConfig implements \JsonSerializable
{
    public const FILE_CREATED ='fileCreated';
    public const FILE_UPDATED = 'fileUpdated';
    public const FILE_DELETED ='fileDeleted';
    public const DIR_CREATED = 'directoryCreated';
    public const DIR_DELETED = 'directoryDeleted';

    public const DEFAULT_EVENTS = [
        self::FILE_CREATED,
        self::FILE_UPDATED,
        self::FILE_DELETED,
        self::DIR_CREATED,
        self::DIR_DELETED
    ];

    private const WATCH_MICROSECONDS = 1000000;

    public function __construct(
        private DirectoryConfig $directories,
        private array $events,
        private int $interval
    ){}

    public function getDirectories() : DirectoryConfig
    {
        return $this->directories;
    }

    public function getInterval() : int
    {
        return $this->interval;
    }

    public function getEvents() : array
    {
        return $this->events;
    }

    public static function fromArray(array $array) : OrchestratorWatcherConfig
    {
        return new self(
            DirectoryConfig::fromArray($array['directories']),
            $array['events'],
            $array['interval']
        );
    }

    public function toArray() : array
    {
        return [
            'directories' => $this->directories->toArray(),
            'events' => $this->events,
            'interval' => $this->interval
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function defaults() : OrchestratorWatcherConfig
    {
        return new self(
            new DirectoryConfig(['.'], [], false),
            self::DEFAULT_EVENTS,
            self::WATCH_MICROSECONDS
        );
    }

}