<?php

declare(strict_types=1);

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

namespace Pimcore\Install\Profile;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class FileInstaller
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $projectRoot;

    public function __construct(LoggerInterface $logger, string $projectRoot)
    {
        $this->logger      = $logger;
        $this->projectRoot = $projectRoot;
    }

    public function installFiles(Profile $profile, bool $overwriteExisting = false, bool $symlink = false): array
    {
        $fs = new Filesystem();
        $errors = [];

        if ($symlink && '\\' === DIRECTORY_SEPARATOR) {
            $this->logger->warning('Symlink was chosen as installation method, but the installer can\'t symlink installation files on Windows. Copying selected files instead');
            $symlink = false;
        }

        $logAction = $symlink ? 'Symlinking' : 'Copying';

        foreach ($profile->getFilesToAdd() as $source => $target) {
            $target = PIMCORE_PROJECT_ROOT . '/' . $target;

            try {
                if ($fs->exists($target)) {
                    if ($overwriteExisting) {
                        $this->logger->warning('Removing existing file {file}', [
                            'file' => $target
                        ]);

                        $fs->remove($target);
                    } else {
                        $this->logger->info('Skipping ' . $logAction . ' {source} to {target}. The target path already exists.');
                        continue;
                    }
                }

                $this->logger->info($logAction . ' {source} to {target}', [
                    'source' => $source,
                    'target' => $target
                ]);

                if ($symlink) {
                    // create symlinks as relative links to make them portable
                    $relativeSource = rtrim($fs->makePathRelative($source, dirname($target)), '/');

                    $fs->symlink($relativeSource, $target);
                } else {
                    if (is_dir($source)) {
                        $fs->mirror($source, $target);
                    } else {
                        $fs->copy($source, $target);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error($e);
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
    }
}
