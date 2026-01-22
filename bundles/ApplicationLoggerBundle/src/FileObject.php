<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\ApplicationLoggerBundle;

use const PIMCORE_PROJECT_ROOT;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use Pimcore\Logger;
use Pimcore\Tool\Storage;

final class FileObject
{
    protected ?string $filename = null;

    protected string $data;

    public function __construct(string $data, ?string $filename = null)
    {
        $this->data = $data;
        $this->filename = $filename;

        if (!$this->filename) {
            $this->filename = date('/Y/m/d/') . uniqid('fileobject_', true);
        }
        $storage = Storage::get('application_log');

        try {
            $storage->write($this->filename, $this->data);
        } catch (FilesystemException | UnableToWriteFile) {
            Logger::warn('Application Logger could not write File Object:'.$this->filename);
        }
    }

    public function getSystemPath(): ?string
    {
        return $this->filename;
    }

    public function getFilename(): string
    {
        return preg_replace('/^'.preg_quote(PIMCORE_PROJECT_ROOT, '/').'/', '', $this->filename);
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return $this->getFilename();
    }
}
