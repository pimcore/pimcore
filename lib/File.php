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

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore;
use Pimcore\Helper\LongRunningHelper;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class File
{
    /**
     * @var null|resource
     */
    protected static $context = null;

    public static function getValidFilename(string $tmpFilename, ?string $language = null, string $replacement = '-'): string
    {
        $tmpFilename = \Pimcore\Tool\Transliteration::toASCII($tmpFilename, $language);
        $tmpFilename = strtolower($tmpFilename);
        $tmpFilename = preg_replace('/[^a-z0-9\-\.~_]+/', $replacement, $tmpFilename);

        // keys shouldn't start with a "." (=hidden file) *nix operating systems
        // keys shouldn't end with a "." - Windows issue: filesystem API trims automatically . at the end of a folder name (no warning ... et al)
        $tmpFilename = trim($tmpFilename, '. ');

        return $tmpFilename;
    }

    public static function putPhpFile(string $path, string $data): void
    {
        $filesystem = new Filesystem();
        $filesystem->dumpFile($path, $data);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path);
        }
    }

    /**
     * @return null|resource
     */
    public static function getContext()
    {
        if (!self::$context) {
            self::$context = stream_context_create([]);
        }

        return self::$context;
    }

    /**
     * @param resource $context
     */
    public static function setContext($context): void
    {
        self::$context = $context;
    }

    public static function getLocalTempFilePath(?string $fileExtension = null, bool $keep = false): string
    {
        $filePath = sprintf('%s/temp-file-%s.%s',
            PIMCORE_SYSTEM_TEMP_DIRECTORY,
            uniqid() . '-' .  bin2hex(random_bytes(15)),
            $fileExtension ?: 'tmp'
        );

        if (!$keep) {
            register_shutdown_function(static function () use ($filePath) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            });

            $longRunningHelper = Pimcore::getContainer()->get(LongRunningHelper::class);
            $longRunningHelper->addTmpFilePath($filePath);
        }

        return $filePath;
    }

    /**
     * @throws FilesystemException
     */
    public static function recursiveDeleteEmptyDirs(FilesystemOperator $storage, string $storagePath): void
    {
        if ($storagePath === '.') {
            return;
        }

        $contents = $storage->listContents($storagePath);
        $count = iterator_count($contents);
        if ($count === 0) {
            $storage->deleteDirectory($storagePath);
            $storagePath = dirname($storagePath);
            self::recursiveDeleteEmptyDirs($storage, $storagePath);
        }
    }
}
