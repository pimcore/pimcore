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
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class Composer
{
    protected static $options = [
        'symfony-app-dir' => 'app',
        'symfony-web-dir' => 'web',
        'symfony-assets-install' => 'hard',
        'symfony-cache-warmup' => false,
    ];

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
    }

    /**
     * @param Event $event
     */
    public static function postUpdate(Event $event)
    {
        $rootPath = self::getRootPath($event);
        self::parametersYmlCheck($rootPath);
    }

    /**
     * @param Event $event
     */
    public static function executeMigrationsUp(Event $event)
    {
        $consoleDir = static::getConsoleDir($event, 'pimcore migrations');

        if (null === $consoleDir) {
            return;
        }

        // execute migrations
        $currentVersion = null;
        try {
            $process = static::executeCommand($event, $consoleDir,
                'pimcore:migrations:status -s pimcore_core -o current_version', 30, false);
            $currentVersion = trim($process->getOutput());
        } catch (\Throwable $e) {
            // noting to do
        }

        if (!empty($currentVersion) && is_numeric($currentVersion)) {
            self::clearDataCache($event, $consoleDir);
            static::executeCommand($event, $consoleDir, 'pimcore:migrations:migrate -s pimcore_core -n');
            self::clearDataCache($event, $consoleDir);
        } else {
            $event->getIO()->write('<comment>Skipping migrations ... (either Pimcore is not installed yet or current status of migrations is not available)</comment>', true);
        }
    }

    /**
     * @param Event $event
     */
    public static function executeBundleMigrationsUp(Event $event)
    {
        $consoleDir = static::getConsoleDir($event, 'bundle migrations');

        if (null === $consoleDir) {
            return;
        }

        $process = static::executeCommand($event, $consoleDir, 'pimcore:bundle:list --json', 30, false);
        $bundles = \json_decode($process->getOutput(), true);

        usort($bundles, static function ($bundle1, $bundle2) {
            return $bundle1['Priority'] <=> $bundle2['Priority'];
        });

        $updatableBundles = array_filter($bundles, static function ($bundle) {
            return $bundle['Updatable'];
        });

        foreach ($updatableBundles as $bundle) {
            try {
                static::executeCommand($event, $consoleDir, 'pimcore:migrations:migrate -b '.$bundle['Bundle']);
            } catch (\Throwable $e) {
                $event->getIO()->write('<comment>Skipping migrations for bundle "'.$bundle.'": '.PHP_EOL.$e.'</comment>', true);
            }
        }

        self::clearDataCache($event, $consoleDir);
    }

    /**
     * @param Event $event
     * @param string $consoleDir
     */
    public static function clearDataCache($event, $consoleDir)
    {
        try {
            static::executeCommand($event, $consoleDir, 'pimcore:cache:clear', 60);
        } catch (\Throwable $e) {
            $event->getIO()->write('<comment>Unable to perform command pimcore:cache:clear</comment>');
        }
    }

    /**
     * @param string $rootPath
     */
    public static function parametersYmlCheck($rootPath)
    {
        // ensure that there's a parameters.yml, if not we'll create a temporary one, so that the requirement check works
        $parameters = '';
        $parametersYml = $rootPath . '/app/config/parameters.yml';
        $parametersYmlExample = $rootPath . '/app/config/parameters.example.yml';
        if (!file_exists($parametersYml) && file_exists($parametersYmlExample)) {
            $parameters = file_get_contents($parametersYmlExample);
        } elseif (file_exists($parametersYml)) {
            $parameters = file_get_contents($parametersYml);
        }

        // ensure that there's a random secret defined
        if (strpos($parameters, 'ThisTokenIsNotSoSecretChangeIt')) {
            $parameters = preg_replace_callback('/ThisTokenIsNotSoSecretChangeIt/', function ($match) {
                // generate a unique token for each occurrence
                return base64_encode(random_bytes(24));
            }, $parameters);
            file_put_contents($parametersYml, $parameters);
        }
    }

    public static function prePackageUpdate(PackageEvent $event)
    {
        /** @var UpdateOperation $operation */
        $operation = $event->getOperation();
        if ($operation->getInitialPackage()->getName() == 'pimcore/pimcore') {
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
            if ($writeBuffer) {
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
        $arguments = [];

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

    /**
     *
     * The following is copied from \Sensio\Bundle\DistributionBundle\Composer\ScriptHandler
     *
     * Installs the assets under the web root directory.
     *
     * For better interoperability, assets are copied instead of symlinked by default.
     *
     * Even if symlinks work on Windows, this is only true on Windows Vista and later,
     * but then, only when running the console with admin rights or when disabling the
     * strict user permission checks (which can be done on Windows 7 but not on Windows
     * Vista).
     *
     * @param Event $event
     */
    public static function installAssets(Event $event)
    {
        $options = static::getOptions($event);
        $consoleDir = static::getConsoleDir($event, 'install assets');

        if (null === $consoleDir) {
            return;
        }

        $webDir = $options['symfony-web-dir'];

        $symlink = '';
        if ('symlink' == $options['symfony-assets-install']) {
            $symlink = '--symlink ';
        } elseif ('relative' == $options['symfony-assets-install']) {
            $symlink = '--symlink --relative ';
        }

        if (!static::hasDirectory($event, 'symfony-web-dir', $webDir, 'install assets')) {
            return;
        }

        static::executeCommand($event, $consoleDir, 'assets:install '.$symlink.escapeshellarg($webDir), $options['process-timeout']);
    }

    /**
     * The following is copied from \Sensio\Bundle\DistributionBundle\Composer\ScriptHandler
     *
     * Clears the Symfony cache.
     *
     * @param Event $event
     */
    public static function clearCache(Event $event)
    {
        $options = static::getOptions($event);
        $consoleDir = static::getConsoleDir($event, 'clear the cache');

        if (null === $consoleDir) {
            return;
        }

        $warmup = '';
        if (!$options['symfony-cache-warmup']) {
            $warmup = ' --no-warmup';
        }

        static::executeCommand($event, $consoleDir, 'cache:clear'.$warmup, $options['process-timeout']);
    }
}
