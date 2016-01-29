<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
    public function getType();

    /**
     * @return integer
     */
    public function getCreationDate();

    /**
     * @param integer $creationDate
     * @return void
     */
    public function setCreationDate($creationDate);

    /**
     * @return integer
     */
    public function getModificationDate();

    /**
     * @param integer $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate);

    /**
     * @return integer
     */
    public function getUserOwner();

    /**
     * @param integer $userOwner
     * @return void
     */
    public function setUserOwner($userOwner);

    /**
     * @return integer
     */
    public function getUserModification();

    /**
     * @param integer $userModification
     * @return void
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
     * @return void
     */
    public function isLocked();

    /**
     * @param  bool $locked
     * @return void
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
    public function getCacheTags($tags = array());
}
