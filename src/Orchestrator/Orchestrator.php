<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Orchestrator;

use Psr\Container\ContainerInterface;
use Pthreat\Orchestrator\Config;
use Pthreat\Orchestrator\Config\Exception\ConfigException;
use Pthreat\Orchestrator\Config\OrchestratorConfig;
use Pthreat\Orchestrator\Orchestrator\Entity\CompileResult;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorCompilerPassReadException;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorEnvReadException;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorEnvWriteException;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorException;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorReadException;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorServiceReadException;
use Pthreat\Orchestrator\Orchestrator\Exception\OrchestratorWriteException;
use Pthreat\Orchestrator\Utility\Fs;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Finder\Finder;

readonly class Orchestrator
{
    private function __construct(
        private Config\OrchestratorConfig $config
    ){
    }

    /**
     * @throws Config\Exception\ConfigException
     */
    public static function factory(string|null $file=null) : Orchestrator
    {
        return new self(Config\OrchestratorConfig::fromJSONFile(
            Fs::mkPath(
                $file ?? getcwd(), OrchestratorConfig::CONFIG_FILE
            )
        ));
    }

    /**
     * @throws ConfigException
     * @throws OrchestratorException
     */
    public static function load(string|null $file=null) : ContainerInterface
    {
        $orchestrator = self::factory($file);
        $outputDirectory = Fs::mkPath(getcwd(), trim($orchestrator->config->getContainerWriteConfig()->getDirectory(), \DIRECTORY_SEPARATOR));
        $containerFile = Fs::mkPath($outputDirectory, $orchestrator->config->getContainerWriteConfig()->getFile());
        $orchestrator->loadEnvFromCacheFile(Fs::mkPath($outputDirectory, 'env-cache'));

        require $containerFile;

        $containerClass = sprintf(
            '%s\\%s',
            $orchestrator->config->getContainerConfig()->getNamespace(),
            $orchestrator->config->getContainerConfig()->getClass()
        );

        $containerClass = preg_replace('#\\\\#', '\\', $containerClass);

        if (!class_exists($containerClass)) {
            $msg = sprintf(
                'FATAL: Container class %s could not be found',
                $containerClass
            );
            throw new Exception\OrchestratorException($msg);
        }

        return new $containerClass();
    }

    /**
     * @throws OrchestratorEnvWriteException
     * @throws OrchestratorEnvReadException
     * @throws OrchestratorWriteException
     * @throws OrchestratorServiceReadException
     * @throws OrchestratorCompilerPassReadException
     * @throws OrchestratorReadException
     */
    public function compile(string|null $envFile = null): Entity\CompileResult
    {
        $outputDirectory = Fs::mkPath(getcwd(), trim($this->config->getContainerWriteConfig()->getDirectory(), \DIRECTORY_SEPARATOR));

        $outputFile = Fs::mkPath($outputDirectory, $this->config->getContainerWriteConfig()->getFile());

        $newOutputFile = Fs::mkPath(
            $outputDirectory,
            sprintf('%s.new', $this->config->getContainerWriteConfig()->getFile())
        );

        if (!is_dir($outputDirectory) && false === @mkdir($outputDirectory, 0755) && !is_dir($outputDirectory)) {
            throw new OrchestratorWriteException("Could not create container output directory $outputDirectory, check file permissions.");
        }

        $containerFileExists = file_exists($newOutputFile);

        if (!$containerFileExists && !touch($newOutputFile)) {
            throw new OrchestratorWriteException("Could not create $newOutputFile, please check file permissions");
        }

        if (!is_writable($newOutputFile)) {
            throw new OrchestratorWriteException("$newOutputFile is not writable, please check file permissions");
        }

        if (!is_readable($newOutputFile)) {
            throw new OrchestratorReadException("$newOutputFile is not readable, please check file permissions");
        }

        $envCacheFile = Fs::mkPath($outputDirectory, 'env-cache');

        $fp = fopen($newOutputFile, 'w');

        if (!$fp) {
            throw new OrchestratorWriteException("Could not open $newOutputFile for writing");
        }

        $build = $this->build();

        $this->write($build->getContainerBuilder(), $newOutputFile);

        if (false === copy($newOutputFile, $outputFile)) {
            throw new OrchestratorWriteException("Could not copy $newOutputFile to $outputFile");
        };

        unlink($newOutputFile);

        $this->generateEnvCache($envCacheFile);
        $this->loadEnvFromCacheFile($envCacheFile);

        if (false === file_exists($outputFile)) {
            $msg = "FATAL ERROR: Could not find container file \"$outputFile\"!";
            throw new OrchestratorWriteException($msg);
        }

        return new CompileResult($build);
    }

    /**
     * @throws OrchestratorServiceReadException
     * @throws OrchestratorCompilerPassReadException
     */
    public function build() : Entity\BuildResult
    {
        $builder = new ContainerBuilder();

        $files = $this->findServiceFiles();

        foreach ($files as $file) {
            $locator = new FileLocator($file);
            $loader = new XmlFileLoader($builder, $locator);
            try {
                $loader->load($file->getRealPath());
            } catch (\Exception $e) {
            }
        }

        /**
         * Find all files matching "^.*CompilerPass.php$" and add them to the container builder.
         */
        $compilerPasses = $this->findCompilerPasses();

        foreach ($compilerPasses as $pass) {

            require $pass->getRealPath();

            $class = get_declared_classes();
            $class = $class[count($class) - 1];

            $builder->addCompilerPass(new $class());
        }

        $builder->compile(false);

        return new Entity\BuildResult(
            $builder,
            iterator_to_array($files, false),
            iterator_to_array($compilerPasses, false)
        );
    }

    /**
     * @throws Exception\OrchestratorServiceReadException
     */
    public function findServiceFiles(): Finder
    {
        try {
            return (new Finder())
                ->files()
                ->ignoreUnreadableDirs($this->config->getServiceDirectories()->isIgnoreUnreadable())
                ->exclude($this->config->getServiceDirectories()->getExcluded())
                ->filter(function (\SplFileInfo $file) : bool{
                    if(!$file->isReadable()){
                        return false;
                    }

                    $fileName = substr($file->getFilename(),0, strpos($file->getFilename(), $file->getExtension())-1);
                    return in_array(strtolower($fileName), $this->config->getServiceFiles()->getPatterns(), true) &&
                        in_array(strtolower($file->getExtension()), $this->config->getServiceFiles()->getExtensions(), true);
                })
                ->in($this->config->getServiceDirectories()->getIncluded());

        }catch(AccessDeniedException $e){
            throw new OrchestratorServiceReadException('Failed to find service files', 0, $e);
        }
    }

    /**
     * @throws Exception\OrchestratorCompilerPassReadException
     */
    public function findCompilerPasses(): Finder
    {
        try {

            return (new Finder())
                ->files()
                ->ignoreUnreadableDirs($this->config->getCompilerPassDirectories()->isIgnoreUnreadable())
                ->exclude($this->config->getCompilerPassDirectories()->getExcluded())
                ->filter(function (\SplFileInfo $file) : bool {
                    foreach($this->config->getCompilerPassFiles()->getPatterns() as $pattern){

                        $isRegex = str_starts_with($pattern, '/');

                        if($isRegex && preg_match($pattern, $file->getFilename())){
                            return true;
                        }

                        if(false === $isRegex && $pattern === $file->getFilename()){
                            return true;
                        }
                    }

                    return false;
                })
                ->in($this->config->getCompilerPassDirectories()->getIncluded());

        }catch(AccessDeniedException $e){
            throw new Exception\OrchestratorCompilerPassReadException('Could not read file while trying to find compiler passes', 0, $e);
        }
    }

    /**
     * @throws OrchestratorEnvReadException
     */
    public function findEnvFiles(): Finder
    {
        try {
            $finder = new Finder();

            return $finder->files()
                ->ignoreDotFiles(false)
                ->ignoreUnreadableDirs($this->config->getEnvironmentDirectories()->isIgnoreUnreadable())
                ->exclude($this->config->getEnvironmentDirectories()->getExcluded())
                ->filter(function (\SplFileInfo $file) : bool{
                    if ($file->isDir()) {
                        return false;
                    }

                    return in_array($file->getFilename(), $this->config->getEnvironmentFiles()->getPatterns(), true);
                })
                ->in($this->config->getEnvironmentDirectories()->getIncluded());
        }catch(AccessDeniedException $e){
            throw new Exception\OrchestratorEnvReadException('Could not read file while trying to find environment files', 0, $e);
        }
    }

    /**
     * @throws OrchestratorWriteException
     */
    public function write(ContainerBuilder $builder, string $file): string {
        $dumper = new PhpDumper($builder);

        $existed = file_exists($file);

        if ($existed) {
            unlink($file);
        }

        try {
            $dump = $dumper->dump($this->config->getContainerConfig()->toArray());
        } catch (\Throwable $e) {
            throw new OrchestratorWriteException('Failed to dump container!', 0, $e);
        }

        $result = @file_put_contents($file, $dump);

        if (false === $result) {
            throw new OrchestratorWriteException("Could not write container file to \"$file\"");
        }

        chmod($file, 0666);

        return $file;
    }

    /**
     * @throws OrchestratorEnvReadException
     * @throws OrchestratorEnvWriteException
     */
    public function generateEnvCache(string $envCacheFile) : void
    {
        /**
         * Find all env files.
         */
        $envFiles = $this->findEnvFiles();

        if (file_exists($envCacheFile)) {
            if (!is_readable($envCacheFile)) {
                $msg = "Env cache file: \"$envCacheFile\" is not readable";
                throw new Exception\OrchestratorEnvReadException($msg);
            }

            unlink($envCacheFile);
        }

        foreach ($envFiles as $env) {
            if(!$env->isReadable()){
                throw new Exception\OrchestratorEnvReadException("Could not read env file {$env->getRealPath()}");
            }

            $result = @file_put_contents($envCacheFile, "{$env->getRealPath()}\n", \FILE_APPEND);

            if(false === $result){
                throw new Exception\OrchestratorEnvWriteException("Could not read env file {$env->getRealPath()}");
            }

            if ($env->isWritable()) {
                chmod($env->getRealPath(), 0666);
            }
        }
    }

    public function loadEnvFromCacheFile(string $file, bool $single = false): bool
    {
        $file = trim($file);

        if (!is_readable($file)) {
            return false;
        }

        if (true === $single) {
            $loader = new Dotenv(true);
            $loader->load($file);

            return true;
        }

        /*
         * Load all env files from cached env file
         */
        foreach (file($file) as $env) {
            $this->loadEnvFromCacheFile($env, true);
        }

        return true;
    }
}
