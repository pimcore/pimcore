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
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Tool\Admin as AdminTool;
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

        foreach ($childrenList as $child) {
            try {
                $children[] = $this->getChild($child);
            } catch (Exception $e) {
                Logger::warning((string) $e);
            }
        }

        return $children;
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
