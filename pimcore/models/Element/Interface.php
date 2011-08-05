<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

interface Element_Interface {

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
     * @return Element_Interface $resource
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
     *
     */

}
