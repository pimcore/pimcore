<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class Composer
{
    protected static $options = array(
        'symfony-app-dir' => 'app',
        'symfony-web-dir' => 'web',
        'symfony-assets-install' => 'hard',
        'symfony-cache-warmup' => false,
    );

    /**
     * @param Event $event
     *
     * @return string
     */
    protected static function getRootPath($event)
    {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        return $rootPath;
    }

    /**
     * @param Event $event
     */
    public static function postCreateProject(Event $event)
    {
        $rootPath = self::getRootPath($event);
        self::parametersYmlCheck($rootPath);
    }

    /**
     * @param Event $event
     */
    public static function postInstall(Event $event)
    {
        $rootPath = self::getRootPath($event);
        self::parametersYmlCheck($rootPath);
        self::zendFrameworkOptimization($rootPath);
    }

    /**
     * @param Event $event
     */
    public static function postUpdate(Event $event)
    {
        $rootPath = self::getRootPath($event);
        self::parametersYmlCheck($rootPath);
        self::zendFrameworkOptimization($rootPath);
    }

    /**
     * @param Event $event
     */
    public static function executeMigrationsUp(Event $event) {
        $consoleDir = static::getConsoleDir($event, 'pimcore migrations');

        if (null === $consoleDir) {
            return;
        }

        $currentVersion = null;
        try {
            $process = static::executeCommand($event, $consoleDir,
                'pimcore:migrations:status -s pimcore_core -o current_version', 30, false);
            $currentVersion = trim($process->getOutput());
        } catch (\Throwable $e) {
            $event->getIO()->write('<comment>Unable to retrieve current migration version</comment>');
        }

        if(!empty($currentVersion)) {
            static::executeCommand($event, $consoleDir, 'pimcore:migrations:migrate -s pimcore_core -n');
        } else {
            $event->getIO()->write('<comment>Skipping migrations, because current version is `0` -> run installer first or mark migrations as done manually!</comment>', true);
        }
    }

    /**
     * @param string $rootPath
     */
    public static function parametersYmlCheck($rootPath)
    {
        // ensure that there's a parameters.yml, if not we'll create a temporary one, so that the requirement check works
        $parametersYml = $rootPath . '/app/config/parameters.yml';
        $parametersYmlExample = $rootPath . '/app/config/parameters.example.yml';
        if (!file_exists($parametersYml) && file_exists($parametersYmlExample)) {
            $secret = base64_encode(random_bytes(24));
            $parameters = file_get_contents($parametersYmlExample);
            $parameters = str_replace('ThisTokenIsNotSoSecretChangeIt', $secret, $parameters);
            file_put_contents($parametersYml, $parameters);
        }
    }

    /**
     * @param $rootPath
     */
    public static function zendFrameworkOptimization($rootPath)
    {
        // @TODO: Remove in 6.0

        // strips all require_once out of the sources
        // see also: http://framework.zend.com/manual/1.10/en/performance.classloading.html#performance.classloading.striprequires.sed
        $zfPath = $rootPath . '/vendor/zendframework/zendframework1/library/Zend/';

        if (is_dir($zfPath)) {
            $directory = new \RecursiveDirectoryIterator($zfPath);
            $iterator = new \RecursiveIteratorIterator($directory);
            $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

            $excludePatterns = [
                '/Loader/Autoloader.php$',
                '/Loader/ClassMapAutoloader.php$',
                '/Application.php$',
            ];

            foreach ($regex as $file) {
                $file = $file[0];

                $excluded = false;
                foreach ($excludePatterns as $pattern) {
                    if (preg_match('@' . $pattern . '@', $file)) {
                        $excluded = true;
                        break;
                    }
                }

                if (!$excluded) {
                    $content = file_get_contents($file);
                    $content = preg_replace('@([^/])(require_once)@', '$1//$2', $content);
                    file_put_contents($file, $content);
                }
            }
        }
    }

    public static function prePackageUpdate(PackageEvent $event) {

        /**
         * @var $operation UpdateOperation
         */
        $operation = $event->getOperation();
        if($operation->getInitialPackage()->getName() == 'pimcore/pimcore') {
            $operation->getInitialPackage()->getSourceReference();
            $operation->getInitialPackage()->getDistReference();

            //@TODO Down migrations, how to implement them?
        }
    }

    /**
     * The following is copied from \Sensio\Bundle\DistributionBundle\Composer\ScriptHandler
     */
    protected static function executeCommand(Event $event, $consoleDir, $cmd, $timeout = 900, $writeBuffer = true)
    {
        $php = escapeshellarg(static::getPhp(false));
        $phpArgs = implode(' ', array_map('escapeshellarg', static::getPhpArguments()));
        $console = escapeshellarg($consoleDir.'/console');
        if ($event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        $process = new Process($php.($phpArgs ? ' '.$phpArgs : '').' '.$console.' '.$cmd, null, null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event, $writeBuffer) {
            if($writeBuffer) {
                $event->getIO()->write($buffer, false);
            }
        });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf("An error occurred when executing the \"%s\" command:\n\n%s\n\n%s", escapeshellarg($cmd), self::removeDecoration($process->getOutput()), self::removeDecoration($process->getErrorOutput())));
        }

        return $process;
    }

    protected static function getPhp($includeArgs = true)
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find($includeArgs)) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }

    protected static function getPhpArguments()
    {
        $ini = null;
        $arguments = array();

        $phpFinder = new PhpExecutableFinder();
        if (method_exists($phpFinder, 'findArguments')) {
            $arguments = $phpFinder->findArguments();
        }

        if ($env = getenv('COMPOSER_ORIGINAL_INIS')) {
            $paths = explode(PATH_SEPARATOR, $env);
            $ini = array_shift($paths);
        } else {
            $ini = php_ini_loaded_file();
        }

        if ($ini) {
            $arguments[] = '--php-ini='.$ini;
        }

        return $arguments;
    }

    protected static function getOptions(Event $event)
    {
        $options = array_merge(static::$options, $event->getComposer()->getPackage()->getExtra());

        $options['symfony-assets-install'] = getenv('SYMFONY_ASSETS_INSTALL') ?: $options['symfony-assets-install'];
        $options['symfony-cache-warmup'] = getenv('SYMFONY_CACHE_WARMUP') ?: $options['symfony-cache-warmup'];

        $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');
        $options['vendor-dir'] = $event->getComposer()->getConfig()->get('vendor-dir');

        return $options;
    }

    protected static function getConsoleDir(Event $event, $actionName)
    {
        $options = static::getOptions($event);

        if (static::useNewDirectoryStructure($options)) {
            if (!static::hasDirectory($event, 'symfony-bin-dir', $options['symfony-bin-dir'], $actionName)) {
                return;
            }

            return $options['symfony-bin-dir'];
        }

        if (!static::hasDirectory($event, 'symfony-app-dir', $options['symfony-app-dir'], 'execute command')) {
            return;
        }

        return $options['symfony-app-dir'];
    }

    protected static function hasDirectory(Event $event, $configName, $path, $actionName)
    {
        if (!is_dir($path)) {
            $event->getIO()->write(sprintf('The %s (%s) specified in composer.json was not found in %s, can not %s.', $configName, $path, getcwd(), $actionName));

            return false;
        }

        return true;
    }

    protected static function useNewDirectoryStructure(array $options)
    {
        return isset($options['symfony-var-dir']) && is_dir($options['symfony-var-dir']);
    }

    private static function removeDecoration($string)
    {
        return preg_replace("/\033\[[^m]*m/", '', $string);
    }
}
