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

class Profile
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $filesToAdd;

    /**
     * @var array
     */
    private $dbDataFiles;

    public function __construct(string $id, string $path, array $config)
    {
        $this->id     = $id;
        $this->name   = $config['name'] ?: $id;
        $this->path   = $path;
        $this->config = $config;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilesToAdd(): array
    {
        if (null !== $this->filesToAdd) {
            return $this->filesToAdd;
        }

        $files  = [];
        $prefix = $this->path . '/';

        foreach ($this->config['files']['add'] as $entry) {
            $pathEntry = $prefix . ltrim($entry, '/');
            $glob      = glob($pathEntry);

            foreach ($glob as $file) {
                // key is the full path to the source file, value the relative
                // path where it should be installed to
                $files[$file] = substr($file, strlen($prefix));
            }
        }

        $this->filesToAdd = $files;

        return $this->filesToAdd;
    }

    public function getDbDataFiles(): array
    {
        if (null !== $this->dbDataFiles) {
            return $this->dbDataFiles;
        }

        $dbFiles = [];
        $prefix  = $this->path . '/';

        foreach ($this->config['db']['data_files'] as $dbFile) {
            $path = $prefix . '/' . ltrim($dbFile, '/');;
            if (!file_exists($path)) {
                throw new \InvalidArgumentException(sprintf(
                    'Profile "%s" defines a non-existing DB file "%s"',
                    $this->getId(),
                    $path
                ));
            }

            $dbFiles[] = realpath($path);
        }

        $this->dbDataFiles = $dbFiles;

        return $this->dbDataFiles;
    }
}
