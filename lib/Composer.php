<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore;

use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * @internal
 */
class Composer
{
    /**
     * @var array<string, mixed>
     */
    protected static array $options = [
        'bin-dir' => 'bin',
        'public-dir' => 'public',
        'symfony-assets-install' => 'copy',
        'symfony-cache-warmup' => false,
    ];

    protected static function getRootPath(Event $event): string
    {
        $config = $event->getComposer()->getConfig();
        $rootPath = dirname($config->get('vendor-dir'));

        return $rootPath;
    }

    public static function postCreateProject(Event $event): void
    {
        $rootPath = self::getRootPath($event);
        self::parametersYmlCheck($rootPath);
    }

    public static function postInstall(Event $event): void
    {
        $rootPath = self::getRootPath($event);
        self::parametersYmlCheck($rootPath);
    }

    public static function postUpdate(Event $event): void
    {
        $rootPath = self::getRootPath($event);
        self::parametersYmlCheck($rootPath);
    }

    public static function clearDataCache(Event $event, string $consoleDir): void
    {
        try {
            static::executeCommand($event, $consoleDir, ['pimcore:cache:clear'], 60);
        } catch (Throwable $e) {
            $event->getIO()->write('<comment>Unable to perform command pimcore:cache:clear</comment>');
        }
    }

    /**
     *
     * @internal
     */
    public static function parametersYmlCheck(string $rootPath): void
    {
        // ensure that there's a parameters.yml, if not we'll create a temporary one, so that the requirement check works
        $parameters = '';
        $parametersYml = $rootPath . '/config/services.yaml';
        if (file_exists($parametersYml)) {
            $parameters = file_get_contents($parametersYml);
        }

        // ensure that there's a random secret defined
        if (strpos($parameters, 'ThisTokenIsNotSoSecretChangeIt')) {
            $parameters = preg_replace_callback('/ThisTokenIsNotSoSecretChangeIt(Immediately)?/', function ($match) {
                // generate a unique token for each occurrence
                return base64_encode(random_bytes(32));
            }, $parameters);
            file_put_contents($parametersYml, $parameters);
        }
    }

    public static function prePackageUpdate(PackageEvent $event): void
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
     *
     * @internal
     */
    protected static function executeCommand(Event $event, string $consoleDir, array $cmd, int $timeout = 900, bool $writeBuffer = true): Process
    {
        $command = [static::getPhp(false)];
        $command = array_merge($command, static::getPhpArguments());

        $command[] = $consoleDir.'/console';
        if ($event->getIO()->isDecorated()) {
            $command[] = '--ansi';
        }

        $command = array_merge($command, $cmd);

        //$event->getIO()->write('Run command: ' . implode(' ', $command), false);

        $process = new Process($command, null, null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event, $writeBuffer) {
            if ($writeBuffer) {
                $event->getIO()->write($buffer, false);
            }
        });
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf("An error occurred when executing the \"%s\" command:\n\nExit code: %d\n\n%s\n\n%s", implode(' ', $command), $process->getExitCode(), self::removeDecoration($process->getOutput()), self::removeDecoration($process->getErrorOutput())));
        }

        return $process;
    }

    /**
     * @internal
     */
    protected static function getPhp(bool $includeArgs = true): string
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find($includeArgs)) {
            throw new RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }

    /**
     * @return string[]
     *
     * @internal
     */
    protected static function getPhpArguments(): array
    {
        $phpFinder = new PhpExecutableFinder();
        $arguments = $phpFinder->findArguments();

        if (!empty($_SERVER['COMPOSER_ORIGINAL_INIS'])) {
            $paths = explode(PATH_SEPARATOR, $_SERVER['COMPOSER_ORIGINAL_INIS']);
            $ini = array_shift($paths);
        } else {
            $ini = php_ini_loaded_file();
        }

        if ($ini) {
            $arguments[] = '--php-ini='.$ini;
        }

        return $arguments;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getOptions(Event $event): array
    {
        $options = array_merge(static::$options, $event->getComposer()->getPackage()->getExtra());

        if (!empty($_SERVER['SYMFONY_ASSETS_INSTALL'])) {
            $options['symfony-assets-install'] = $_SERVER['SYMFONY_ASSETS_INSTALL'];
        }

        if (!empty($_SERVER['SYMFONY_CACHE_WARMUP'])) {
            $options['symfony-cache-warmup'] = $_SERVER['SYMFONY_CACHE_WARMUP'];
        }

        $options['vendor-dir'] = $event->getComposer()->getConfig()->get('vendor-dir');

        return $options;
    }

    protected static function getConsoleDir(Event $event, string $actionName): ?string
    {
        $options = static::getOptions($event);
        if (!static::hasDirectory($event, 'bin-dir', $options['bin-dir'], $actionName)) {
            return null;
        }

        return $options['bin-dir'];
    }

    protected static function hasDirectory(Event $event, string $configName, string $path, string $actionName): bool
    {
        if (!is_dir($path)) {
            $event->getIO()->write(sprintf('The %s (%s) specified in composer.json was not found in %s, can not %s.', $configName, $path, getcwd(), $actionName));

            return false;
        }

        return true;
    }

    private static function removeDecoration(string $string): string
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
     */
    public static function installAssets(Event $event): void
    {
        $options = static::getOptions($event);
        $consoleDir = static::getConsoleDir($event, 'install assets');

        if (null === $consoleDir) {
            return;
        }

        $command = ['assets:install'];
        $webDir = $options['public-dir'];

        if ('symlink' == $options['symfony-assets-install']) {
            $command[] = '--symlink';
        } elseif ('relative' == $options['symfony-assets-install']) {
            array_push($command, '--symlink', '--relative');
        }

        $command[] = '--ignore-maintenance-mode';

        if (!static::hasDirectory($event, 'public-dir', $webDir, 'install assets')) {
            return;
        }

        $command[] = $webDir;

        static::executeCommand($event, $consoleDir, $command);
    }

    /**
     * The following is copied from \Sensio\Bundle\DistributionBundle\Composer\ScriptHandler
     *
     * Clears the Symfony cache.
     *
     */
    public static function clearCache(Event $event): void
    {
        $options = static::getOptions($event);
        $consoleDir = static::getConsoleDir($event, 'clear the cache');

        if (null === $consoleDir) {
            return;
        }

        $command = ['cache:clear'];
        if (!$options['symfony-cache-warmup']) {
            $command[] = '--no-warmup';
            $command[] = '--ignore-maintenance-mode';
        }

        static::executeCommand($event, $consoleDir, $command);
    }
}
