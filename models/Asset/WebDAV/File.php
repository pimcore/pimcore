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
            if ($user === null) {
                throw new DAV\Exception\Forbidden('No authenticated user available');
            }
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
            $path = $this->asset->getRealFullPath();
            $id = $this->asset->getId();
            $userOwner = $this->asset->getUserOwner();
            $creationDate = $this->asset->getCreationDate();

            // Snapshot the asset's own properties and metadata as plain scalar rows *before*
            // deleting, so the destination can be restored on a delete + create + move (see
            // Asset\WebDAV\Tree::move()). Only scalars are stored, so the delete log never
            // needs to deserialize objects.
            $db = \Pimcore\Db::get();
            $properties = $db->fetchAllAssociative(
                "SELECT `name`, `type`, `data`, `inheritable` FROM properties WHERE cid = ? AND ctype = 'asset'",
                [$id]
            );
            $metadata = $db->fetchAllAssociative(
                'SELECT `name`, `type`, `data`, `language` FROM assets_metadata WHERE cid = ?',
                [$id]
            );

            $this->asset->delete();

            // record the deleted asset's id (plus the scalar property/metadata snapshot) so
            // the destination can keep its id - and thus any hardcoded references - and its
            // metadata across a delete + create + move. For details see Asset\WebDAV\Tree::move().
            $log = Asset\WebDAV\Service::getDeleteLog();
            $log[$path] = [
                'id' => $id,
                'timestamp' => time(),
                'userOwner' => $userOwner,
                'creationDate' => $creationDate,
                'properties' => $properties,
                'metadata' => $metadata,
            ];

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
            $file = null;

            try {
                if (file_put_contents($tmpFile, $data) === false) {
                    throw new DAV\Exception('Unable to write temporary file');
                }
                $file = fopen($tmpFile, 'r+', false, FileHelper::getContext());
                if (!is_resource($file)) {
                    // Asset::setStream() silently ignores non-resource values, which would
                    // let save() "succeed" without replacing the data -> fail loudly instead
                    throw new DAV\Exception('Unable to open temporary file');
                }

                $user = AdminTool::getCurrentUser();
                if ($user === null) {
                    throw new DAV\Exception\Forbidden('No authenticated user available');
                }
                $this->asset->setUserModification($user->getId());

                $this->asset->setStream($file);
                $this->asset->save();

                return null;
            } finally {
                if (is_resource($file)) {
                    fclose($file);
                }
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }
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
