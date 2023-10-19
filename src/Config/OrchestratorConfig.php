<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Config;

use Pthreat\Orchestrator\Config\Exception\ConfigException;
use Pthreat\Orchestrator\Utility\Fs;

readonly class OrchestratorConfig
{
    public const CONFIG_FILE = 'orchestrator.json';
    public const DEFAULT_EXCLUDED_DIRECTORIES = ['vendor'];
    private const CONTAINER_NAMESPACE = 'Pthreat\\Orchestrator';
    private const CONTAINER_CLASS = 'Orchestrator';
    private const DEFAULT_SERVICE_FILENAMES = ['services', 'commands', 'loggers', 'events', 'guards'];
    private const DEFAULT_SERVICE_EXTENSIONS = ['xml'];
    private const DEFAULT_ENVIRONMENT_FILES = ['env'];
    private const DEFAULT_COMPILER_PASSES = ['/^.*CompilerPass.php$/'];

    public function __construct(
        private Entity\ContainerWriteConfig $writeConfig,
        private Entity\ContainerConfig $containerConfig,
        private Entity\DirectoryConfig $environmentDirectories,
        private Entity\FilesConfig     $environmentFiles,
        private Entity\EnvWriteConfig  $envWriteConfig,
        private Entity\DirectoryConfig $serviceDirectories,
        private Entity\FilesConfig     $serviceFiles,
        private Entity\DirectoryConfig $compilerPassDirectories,
        private Entity\FilesConfig     $compilerPassFiles
    )
    {}

    /**
     * @throws Exception\ConfigException
     */
    public static function init(string $directory) : string
    {
        $directory = realpath($directory);

        if (false === $directory || !is_writable($directory)) {
            throw new Exception\ConfigException('Could not initialize orchestrator configuration in given directory');
        }

        $config = [
            'container' => [
                'config' => new Entity\ContainerConfig(self::CONTAINER_NAMESPACE, self::CONTAINER_CLASS),
                'write' => new Entity\ContainerWriteConfig('cache', 'container.php')
            ],
            'environment' => [
                'directories' => new Entity\DirectoryConfig(['.'], self::DEFAULT_EXCLUDED_DIRECTORIES, false),
                'files' => new Entity\FilesConfig(self::DEFAULT_ENVIRONMENT_FILES, [], false),
                'write' => new Entity\EnvWriteConfig('cache', '.env')
            ],
            'services' => [
                'directories' => new Entity\DirectoryConfig(['.'], self::DEFAULT_EXCLUDED_DIRECTORIES, false),
                'files' => new Entity\FilesConfig(self::DEFAULT_SERVICE_FILENAMES, self::DEFAULT_SERVICE_EXTENSIONS, false)
            ],
            'passes' => [
                'directories' => new Entity\DirectoryConfig(['.'], self::DEFAULT_EXCLUDED_DIRECTORIES, false),
                'files' => new Entity\FilesConfig(self::DEFAULT_COMPILER_PASSES, [], false)
            ]
        ];

        $output = Fs::mkPath($directory, self::CONFIG_FILE);
        file_put_contents($output, json_encode($config, \JSON_PRETTY_PRINT));

        return $output;
    }

    public function getEnvWriteConfig() : Entity\EnvWriteConfig
    {
        return $this->envWriteConfig;
    }

    public function getContainerWriteConfig() : Entity\ContainerWriteConfig
    {
        return $this->writeConfig;
    }

    public function getServiceFiles() : Entity\FilesConfig
    {
        return $this->serviceFiles;
    }

    public function getServiceDirectories() : Entity\DirectoryConfig
    {
        return $this->serviceDirectories;
    }

    public function getContainerConfig() : Entity\ContainerConfig
    {
        return $this->containerConfig;
    }

    public function getCompilerPassDirectories() : Entity\DirectoryConfig
    {
        return $this->compilerPassDirectories;
    }

    public function getCompilerPassFiles() : Entity\FilesConfig
    {
        return $this->compilerPassFiles;
    }

    public function getEnvironmentDirectories() : Entity\DirectoryConfig
    {
        return $this->environmentDirectories;
    }

    public function getEnvironmentFiles() : Entity\FilesConfig
    {
        return $this->environmentFiles;
    }

    /**
     * @throws Exception\ConfigFileNotFoundException
     * @throws Exception\ConfigReadException
     * @throws Exception\ConfigJSONDecodeException
     * @throws Exception\ConfigException
     */
    public static function fromJSONFile(string $file) : OrchestratorConfig
    {
        $path = realpath($file);

        if(false === $path){
            throw new Exception\ConfigFileNotFoundException("Could not find $file");
        }

        if(!is_readable($path)){
            throw new Exception\ConfigReadException("$path is not readable!");
        }

        return self::fromJSON(file_get_contents($path));
    }

    /**
     * @throws Exception\ConfigJSONDecodeException
     * @throws Exception\ConfigException
     */
    public static function fromJSON(string $json) : OrchestratorConfig
    {
        try {
            return self::fromArray(json_decode($json, true, 1024, \JSON_THROW_ON_ERROR));
        }catch(\JsonException $e){
            throw new Exception\ConfigJSONDecodeException('Could not decode JSON', 0, $e);
        }
    }

    /**
     * @throws ConfigException
     */
    public static function fromArray(array $config) : OrchestratorConfig
    {
        try {
            return new self(
                Entity\ContainerWriteConfig::fromArray($config['container']['write']),
                Entity\ContainerConfig::fromArray($config['container']['config']),
                Entity\DirectoryConfig::fromArray($config['environment']['directories']),
                Entity\FilesConfig::fromArray($config['environment']['files']),
                Entity\EnvWriteConfig::fromArray($config['environment']['write']),
                Entity\DirectoryConfig::fromArray($config['services']['directories']),
                Entity\FilesConfig::fromArray($config['services']['files']),
                Entity\DirectoryConfig::fromArray($config['passes']['directories']),
                Entity\FilesConfig::fromArray($config['passes']['files']),
            );
        }catch(\Throwable $e){
            throw new Exception\ConfigException('Malformed configuration file', 0, $e);
        }
    }
}
