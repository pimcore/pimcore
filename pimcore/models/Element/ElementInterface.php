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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

interface ElementInterface
{

    /**
     * @return integer $id
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
     * @return integer
     */
    public function getCreationDate();

    /**
     * @param integer $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate);

    /**
     * @return integer
     */
    public function getModificationDate();

    /**
     * @param integer $modificationDate
     * @return $this
     */
    public function setModificationDate($modificationDate);

    /**
     * @return integer
     */
    public function getUserOwner();

    /**
     * @param integer $userOwner
     * @return $this
     */
    public function setUserOwner($userOwner);

    /**
     * @return integer
     */
    public function getUserModification();

    /**
     * @param integer $userModification
     * @return $this
     */
    public function setUserModification($userModification);

    /**
     *
     * @param $id
     * @return ElementInterface $resource
     */
    public static function getById($id);

    /**
     * get possible types
     * @return array
     */
    public static function getTypes();

    /**
     * @return array
     */
    public function getProperties();

    /**
     * returns true if the element is locked
     * @return $this
     */
    public function isLocked();

    /**
     * @param  bool $locked
     * @return $this
     */
    public function setLocked($locked);

    /**
     * @return integer
     */
    public function getParentId();

    /**
     * @return string
     */
    public function getCacheTag();

    /**
     * @param array $tags
     * @return array
     */
    public function getCacheTags($tags = []);

    /**
     * @return bool
     */
    public function __isBasedOnLatestData();
}
