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
use League\Flysystem\FileAttributes;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Tool\Storage;
use Sabre\DAV;

/**
 * @internal
 */
class Folder extends DAV\Collection
{
    private Asset $asset;

    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    /**
     * Returns the children of the asset if the asset is a folder
     *
     */
    public function getChildren(): array
    {
        $childrenList = new Asset\Listing();

        $childrenList->addConditionParam('parentId = ?', [$this->asset->getId()]);
        $user = \Pimcore\Tool\Admin::getCurrentUser();
        $childrenList->filterAccessibleByUser($user, $this->asset);

        $assets = $childrenList->getAssets();

        // only files have a size, so a folder without any needs no storage listing
        $fileSizes = [];
        foreach ($assets as $child) {
            if (!$child instanceof Asset\Folder) {
                $fileSizes = $this->getFileSizes();

                break;
            }
        }

        $children = [];
        foreach ($assets as $child) {
            $children[] = $this->createNode($child, $fileSizes[$child->getRealFullPath()] ?? null);
        }

        return $children;
    }

    /**
     * Reads the size of every file in this folder with a single storage listing.
     *
     * A PROPFIND asks for the size of each child, and asking the storage per file costs one
     * request per child on remote storage (S3, Azure Blob, ...), which does not finish in time
     * for large folders. Children missing from the listing fall back to the per-file lookup, and
     * so does every child if the listing fails, e.g. at a symbolic link on local storage.
     *
     * @return array<string, int> file sizes in bytes, keyed by the asset's full path
     */
    private function getFileSizes(): array
    {
        $fileSizes = [];

        try {
            foreach (Storage::get('asset')->listContents($this->asset->getRealFullPath(), false) as $item) {
                if ($item instanceof FileAttributes && $item->fileSize() !== null) {
                    $fileSizes['/' . ltrim($item->path(), '/')] = $item->fileSize();
                }
            }
        } catch (Exception $e) {
            Logger::info('Unable to list the contents of asset folder ' . $this->asset->getRealFullPath() . ', falling back to a per-file size lookup: ' . $e);
        }

        return $fileSizes;
    }

    private function createNode(Asset $asset, ?int $size = null): File|Folder
    {
        if ($asset instanceof Asset\Folder) {
            return new Asset\WebDAV\Folder($asset);
        }

        return new Asset\WebDAV\File($asset, $size);
    }

    /**
     * @param Asset|string $name
     *
     * @throws DAV\Exception\NotFound
     */
    public function getChild($name): File|Folder
    {
        $asset = null;

        if (is_string($name)) {
            $name = Element\Service::getValidKey(basename($name), 'asset');

            $parentPath = $this->asset->getRealFullPath();
            if ($parentPath === '/') {
                $parentPath = '';
            }

            $asset = Asset::getByPath($parentPath . '/' . $name);
        } elseif ($name instanceof Asset) {
            $asset = $name;
        }

        if ($asset instanceof Asset) {
            return $this->createNode($asset);
        }

        throw new DAV\Exception\NotFound('File not found: ' . $name);
    }

    public function getName(): string
    {
        return $this->asset->getFilename();
    }

    /**
     * @param string $name
     * @param string|resource|null $data
     *
     * @throws DAV\Exception\Forbidden
     *
     * @return null
     */
    public function createFile($name, $data = null)
    {
        // check authentication and permission before touching the disk, so an unauthorized
        // request never causes a temp-file write
        $user = AdminTool::getCurrentUser();
        if ($user === null) {
            throw new DAV\Exception\Forbidden('No authenticated user available');
        }

        if (!$this->asset->isAllowed('create')) {
            throw new DAV\Exception\Forbidden('Missing "create" permission');
        }

        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/asset-dav-tmp-file-' . uniqid();
        if (is_resource($data)) {
            @rewind($data);
        }

        try {
            if (file_put_contents($tmpFile, $data) === false) {
                throw new DAV\Exception('Unable to write temporary file');
            }

            Asset::create($this->asset->getId(), [
                'filename' => Element\Service::getValidKey($name, 'asset'),
                'sourcePath' => $tmpFile,
                'userModification' => $user->getId(),
                'userOwner' => $user->getId(),
            ]);

            return null;
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    /**
     * @param string $name
     *
     * @throws DAV\Exception\Forbidden
     */
    public function createDirectory($name): void
    {
        $user = AdminTool::getCurrentUser();
        if ($user === null) {
            throw new DAV\Exception\Forbidden('No authenticated user available');
        }

        if ($this->asset->isAllowed('create')) {
            $asset = Asset::create($this->asset->getId(), [
                'filename' => Element\Service::getValidKey($name, 'asset'),
                'type' => 'folder',
                'userModification' => $user->getId(),
                'userOwner' => $user->getId(),
            ]);
        } else {
            throw new DAV\Exception\Forbidden('Missing "create" permission');
        }
    }

    /**
     * @throws DAV\Exception\Forbidden
     * @throws Exception
     */
    public function delete(): void
    {
        if ($this->asset->isAllowed('delete')) {
            $this->asset->delete();
        } else {
            throw new DAV\Exception\Forbidden('Missing "delete" permission');
        }
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
            $this->asset->setFilename(Element\Service::getValidKey($name, 'asset'));
            $this->asset->save();
        } else {
            throw new DAV\Exception\Forbidden('Missing "rename" permission');
        }

        return $this;
    }

    public function getLastModified(): int
    {
        return $this->asset->getModificationDate();
    }
}
