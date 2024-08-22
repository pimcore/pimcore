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

namespace Pimcore\Model\Asset\WebDAV;

use Exception;
use Pimcore\File as FileHelper;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Tool\Admin as AdminTool;
use Sabre\DAV;

/**
 * @internal
 */
class File extends DAV\File
{
    private Asset $asset;

    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    public function getName(): string
    {
        return $this->asset->getFilename();
    }

    /**
     * @param string $name
     *
     * @return $this
     *
     * @throws DAV\Exception\Forbidden
     * @throws Exception
     */
    public function setName($name): static
    {
        if ($this->asset->isAllowed('rename')) {
            $user = AdminTool::getCurrentUser();
            $this->asset->setUserModification($user->getId());

            $this->asset->setFilename(Element\Service::getValidKey($name, 'asset'));
            $this->asset->save();
        } else {
            throw new DAV\Exception\Forbidden();
        }

        return $this;
    }

    /**
     * @throws DAV\Exception\Forbidden
     * @throws Exception
     */
    public function delete(): void
    {
        if ($this->asset->isAllowed('delete')) {
            Asset\Service::loadAllFields($this->asset);
            $this->asset->delete();

            // add the asset to the delete history, this is used so come over problems with programs like photoshop (delete, create instead of replace => move)
            // for details see Asset\WebDAV\Tree::move()
            $log = Asset\WebDAV\Service::getDeleteLog();

            $this->asset->setInDumpState(true);
            $log[$this->asset->getRealFullPath()] = [
                'id' => $this->asset->getId(),
                'timestamp' => time(),
                'data' => \Pimcore\Tool\Serialize::serialize($this->asset),
            ];

            $this->asset->setInDumpState(false);

            Asset\WebDAV\Service::saveDeleteLog($log);
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    public function getLastModified(): int
    {
        return $this->asset->getModificationDate();
    }

    /**
     * @param resource $data
     *
     * @throws DAV\Exception\Forbidden
     * @throws Exception
     *
     * @return null
     */
    public function put($data)
    {
        if ($this->asset->isAllowed('publish')) {
            // read from resource -> default for SabreDAV
            $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/asset-dav-tmp-file-' . uniqid();
            file_put_contents($tmpFile, $data);
            $file = fopen($tmpFile, 'r+', false, FileHelper::getContext());

            $user = AdminTool::getCurrentUser();
            $this->asset->setUserModification($user->getId());

            $this->asset->setStream($file);
            $this->asset->save();

            if (is_resource($file)) {
                fclose($file);
            }
            unlink($tmpFile);

            return null;
        }

        throw new DAV\Exception\Forbidden();
    }

    /**
     * @return resource|null
     *
     * @throws DAV\Exception\Forbidden
     */
    public function get()
    {
        if ($this->asset->isAllowed('view')) {
            return $this->asset->getStream();
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * Get a hash of the file for an unique identifier
     *
     */
    public function getETag(): string
    {
        return '"' . md5($this->asset->getRealFullPath() . $this->asset->getModificationDate()) . '"';
    }

    /**
     * Returns the mimetype of the asset
     *
     */
    public function getContentType(): string
    {
        return $this->asset->getMimeType();
    }

    /**
     * Get size of file in bytes
     *
     */
    public function getSize(): int
    {
        return $this->asset->getFileSize();
    }
}
