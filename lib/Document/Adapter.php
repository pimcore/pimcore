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

namespace Pimcore\Document;

use Pimcore\Model\Asset;

/**
 * @internal
 */
abstract class Adapter
{
    /**
     * @var null|Asset\Document
     */
    protected $asset;

    /**
     * @var array
     */
    protected $tmpFiles = [];

    protected function removeTmpFiles()
    {
        // remove tmp files
        if (!empty($this->tmpFiles)) {
            foreach ($this->tmpFiles as $tmpFile) {
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }
        }
    }

    public function __destruct()
    {
        $this->removeTmpFiles();
    }

    /**
     * @param Asset\Document $asset
     *
     * @return $this
     */
    abstract public function load(Asset\Document $asset);

    /**
     * @param string $imageTargetPath
     * @param int $page
     * @param int $resolution
     *
     * @return mixed
     */
    abstract public function saveImage(string $imageTargetPath, $page = 1, $resolution = 200);

    /**
     * @param Asset\Document|null $asset
     *
     * @return resource
     */
    abstract public function getPdf(?Asset\Document $asset = null);

    /**
     * @param string $fileType
     *
     * @return bool
     */
    abstract public function isFileTypeSupported($fileType);

    /**
     * @return int
     *
     * @throws \Exception
     */
    abstract public function getPageCount();

    /**
     * @param null|int $page
     * @param Asset\Document|null $asset
     *
     * @return mixed
     */
    abstract public function getText(?int $page = null, ?Asset\Document $asset = null);
}
