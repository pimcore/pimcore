<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\WebDAV;

use Sabre\DAV;
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Logger;

class Folder extends DAV\Collection
{

    /**
     * @var Asset
     */
    private $asset;

    /**
     * @param $asset
     */
    public function __construct($asset)
    {
        $this->asset = $asset;
    }

    /**
     * Returns the children of the asset if the asset is a folder
     *
     * @return array
     */
    public function getChildren()
    {
        $children = [];

        if ($this->asset->hasChildren()) {
            foreach ($this->asset->getChildren() as $child) {
                if ($child->isAllowed("view")) {
                    try {
                        if ($child = $this->getChild($child)) {
                            $children[] = $child;
                        }
                    } catch (\Exception $e) {
                        Logger::warning($e);
                    }
                }
            }
        }

        return $children;
    }

    /**
     * @param string $name
     * @return DAV\INode|void
     * @throws DAV\Exception\NotFound
     */
    public function getChild($name)
    {
        $nameParts = explode("/", $name);
        $name = Element\Service::getValidKey($nameParts[count($nameParts)-1], "asset");

        if (is_string($name)) {
            $parentPath = $this->asset->getRealFullPath();
            if ($parentPath == "/") {
                $parentPath = "";
            }

            if (!$asset = Asset::getByPath($parentPath . "/" . $name)) {
                throw new DAV\Exception\NotFound('File not found: ' . $name);
            }
        } elseif ($name instanceof Asset) {
            $asset = $name;
        }

        if ($asset instanceof Asset) {
            if ($asset->getType() == "folder") {
                return new Asset\WebDAV\Folder($asset);
            } else {
                return new Asset\WebDAV\File($asset);
            }
        }
        throw new DAV\Exception\NotFound('File not found: ' . $name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->asset->getFilename();
    }

    /**
     * @param string $name
     * @param null $data
     * @return null|string|void
     * @throws DAV\Exception\Forbidden
     */
    public function createFile($name, $data = null)
    {
        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/asset-dav-tmp-file-" . uniqid();
        file_put_contents($tmpFile, $data);

        $user = AdminTool::getCurrentUser();

        if ($this->asset->isAllowed("create")) {
            $asset = Asset::create($this->asset->getId(), [
                "filename" => Element\Service::getValidKey($name, "asset"),
                "sourcePath" => $tmpFile,
                "userModification" => $user->getId(),
                "userOwner" => $user->getId()
            ]);

            unlink($tmpFile);
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @param string $name
     * @throws DAV\Exception\Forbidden
     */
    public function createDirectory($name)
    {
        $user = AdminTool::getCurrentUser();

        if ($this->asset->isAllowed("create")) {
            $asset = Asset::create($this->asset->getId(), [
                "filename" => Element\Service::getValidKey($name, "asset"),
                "type" => "folder",
                "userModification" => $user->getId(),
                "userOwner" => $user->getId()
            ]);
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    public function delete()
    {
        if ($this->asset->isAllowed("delete")) {
            $this->asset->delete();
        } else {
            throw new DAV\Exception\Forbidden();
        }
    }

    /**
     * @param string $name
     * @return $this|void
     * @throws DAV\Exception\Forbidden
     * @throws \Exception
     */
    public function setName($name)
    {
        if ($this->asset->isAllowed("rename")) {
            $this->asset->setFilename(Element\Service::getValidKey($name, "asset"));
            $this->asset->save();
        } else {
            throw new DAV\Exception\Forbidden();
        }

        return $this;
    }

    /**
     * @return integer
     */
    public function getLastModified()
    {
        return $this->asset->getModificationDate();
    }
}
