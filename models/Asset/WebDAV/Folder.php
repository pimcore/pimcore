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
use League\Flysystem\FilesystemException;
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
        $children = [];

        $childrenList = new Asset\Listing();

        $childrenList->addConditionParam('parentId = ?', [$this->asset->getId()]);
        $user = \Pimcore\Tool\Admin::getCurrentUser();
        $childrenList->filterAccessibleByUser($user, $this->asset);

        $fileSizes = $this->getFileSizes();

        foreach ($childrenList as $child) {
            try {
                if ($child instanceof Asset\Folder) {
                    $children[] = new Asset\WebDAV\Folder($child);
                } else {
                    $children[] = new Asset\WebDAV\File($child, $fileSizes[$child->getRealFullPath()] ?? null);
                }
            } catch (Exception $e) {
                Logger::warning((string) $e);
            }
        }

        return $children;
    }

    /**
     * Reads the size of every file in this folder with a single storage listing.
     *
     * A PROPFIND asks for the size of each child, and asking the storage per file costs one
     * request per child on remote storage (S3, Azure Blob, ...), which does not finish in time
     * for large folders. Children missing from the listing fall back to the per-file lookup.
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
        } catch (FilesystemException $e) {
            Logger::warning('Unable to list the contents of asset folder ' . $this->asset->getRealFullPath() . ': ' . $e);
        }

        return $fileSizes;
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
            if ($asset instanceof Asset\Folder) {
                return new Asset\WebDAV\Folder($asset);
            }

            return new Asset\WebDAV\File($asset);
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
        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/asset-dav-tmp-file-' . uniqid();
        if (is_resource($data)) {
            @rewind($data);
        }
        file_put_contents($tmpFile, $data);

        $user = AdminTool::getCurrentUser();

        if ($this->asset->isAllowed('create')) {
            Asset::create($this->asset->getId(), [
                'filename' => Element\Service::getValidKey($name, 'asset'),
                'sourcePath' => $tmpFile,
                'userModification' => $user->getId(),
                'userOwner' => $user->getId(),
            ]);

            unlink($tmpFile);

            return null;
        }

        unlink($tmpFile);

        throw new DAV\Exception\Forbidden('Missing "create" permission');
    }

    /**
     * @param string $name
     *
     * @throws DAV\Exception\Forbidden
     */
    public function createDirectory($name): void
    {
        $user = AdminTool::getCurrentUser();

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
