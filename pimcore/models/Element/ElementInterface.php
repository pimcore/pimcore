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

interface ElementInterface
{
    /**
     * @return int $id
     */
    public function getId();

    /**
     * @return string
     */
    public function getKey();

    /**
     * @return string
     */
    public function getPath();

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
     * @param $id
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
     * @return array
     */
    public function getProperties();

    /**
     * returns true if the element is locked
     *
     * @return $this
     */
    public function isLocked();

    /**
     * @param  bool $locked
     *
     * @return $this
     */
    public function setLocked($locked);

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
}
