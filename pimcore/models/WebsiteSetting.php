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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class WebsiteSetting extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;
    
    /**
     * @var string
     */
    public $name;

    /**
     * @var
     */
    public $type;

    /**
     * @var
     */
    public $data;

    /**
     * @var
     */
    public $siteId;

    /**
     * @var
     */
    public $creationDate;

    /**
     * @var
     */
    public $modificationDate;



    /**
     * @param integer $id
     * @return WebsiteSetting
     */
    public static function getById($id) {
        $setting = new self();

        $setting->setId(intval($id));
        $setting->getResource()->getById();
        return $setting;
    }
    
    /**
     * @param string $name
     * @return WebsiteSetting
     */
    public static function getByName($name, $siteId = null) {

        // create a tmp object to obtain the id
        $setting = new self();

        try {
            $setting->getResource()->getByName($name, $siteId);
        } catch (Exception $e) {
            Logger::error($e);
            return null;
        }
        return $setting;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }


    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }


    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param mixed $siteId
     */
    public function setSiteId($siteId)
    {
        $this->siteId = (int) $siteId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSiteId()
    {
        return (int) $this->siteId;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return void
     */
    public function clearDependentCache() {

    }


}
