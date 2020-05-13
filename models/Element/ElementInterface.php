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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Model\ModelInterface;
use Pimcore\Model\Property;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\User;
use Pimcore\Model\Version;

interface ElementInterface extends ModelInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getKey();

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key);

    /**
     * @return string
     */
    public function getPath();

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path);

    /**
     * @return string
     */
    public function getRealPath();

    /**
     * @return string
     */
    public function getFullPath();

    /**
     * @return string
     */
    public function getRealFullPath();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return int
     */
    public function getCreationDate();

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate($creationDate);

    /**
     * @return int
     */
    public function getModificationDate();

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate);

    /**
     * @return int
     */
    public function getUserOwner();

    /**
     * @param int $userOwner
     *
     * @return $this
     */
    public function setUserOwner($userOwner);

    /**
     * @return int
     */
    public function getUserModification();

    /**
     * @param int $userModification
     *
     * @return $this
     */
    public function setUserModification($userModification);

    /**
     *
     * @param int $id
     *
     * @return ElementInterface $resource
     */
    public static function getById($id);

    /**
     * get possible types
     *
     * @return array
     */
    public static function getTypes();

    /**
     * @return Property[]
     */
    public function getProperties();

    /**
     * returns true if the element is locked
     *
     * @return bool
     */
    public function isLocked();

    /**
     * enum('self','propagate') nullable
     *
     * @param string|null $locked
     *
     * @return $this
     */
    public function setLocked($locked);

    /**
     * enum('self','propagate') nullable
     *
     * @return string|null
     */
    public function getLocked();

    /**
     * @return int
     */
    public function getParentId();

    /**
     * @return string
     */
    public function getCacheTag();

    /**
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags($tags = []);

    /**
     * @return bool
     */
    public function __isBasedOnLatestData();

    /**
     * @param int|null $versionCount
     *
     * @return self
     */
    public function setVersionCount(?int $versionCount): self;

    /**
     * @return int
     */
    public function getVersionCount(): int;

    /**
     * @return $this
     */
    public function save();

    public function delete();

    /**
     * @param array $additionalTags
     */
    public function clearDependentCache($additionalTags = []);

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id);

    /**
     * This is used for user-permissions, pass a permission type (eg. list, view, save) an you know if the current user is allowed to perform the requested action
     *
     * @param string $type
     * @param null|User $user
     *
     * @return bool
     */
    public function isAllowed($type, ?User $user = null);

    /**
     * @return Task[]
     */
    public function getScheduledTasks();

    /**
     * @return Version[]
     */
    public function getVersions();
}
