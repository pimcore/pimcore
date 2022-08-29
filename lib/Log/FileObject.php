<?php

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

namespace Pimcore\Log;

use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use Pimcore\Logger;
use Pimcore\Tool\Storage;

final class FileObject
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $data;

    /**
     * @param string $data
     * @param string $filename
     */
    public function __construct($data, $filename = null)
    {
        $this->data = $data;
        $this->filename = $filename;

        if (empty($this->filename)) {
            $folderpath = strftime('/%Y/%m/%d');
            $this->filename = $folderpath.'/'.uniqid('fileobject_', true);
        }
        $storage = Storage::get('application_log');

        try {
            $storage->write($this->filename, $this->data);
        } catch (FilesystemException | UnableToWriteFile $exception) {
            Logger::warn('Application Logger could not write File Object:'.$this->filename);
        }
    }

    /**
     * @return string
     */
    public function getSystemPath()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return preg_replace('/^'.preg_quote(\PIMCORE_PROJECT_ROOT, '/').'/', '', $this->filename);
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return $this->getFilename();
    }
}
