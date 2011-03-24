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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */ 

class Asset_Image_Thumbnail extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var integer
     */
    public $width = 0;

    /**
     * @var integer
     */
    public $height = 0;

    /**
     * @var boolean
     */
    public $aspectratio = true;

    /**
     * @var boolean
     */
    public $cover = false;

    /**
     * @var boolean
     */
    public $contain = false;

    /**
     * @var boolean
     */
    public $interlace = true;

    /**
     * @var integer
     */
    public $quality = 90;

    /**
     * @var string
     */
    public $format = "SOURCE";

    /**
     * @param integer $id
     * @return Thumbnail
     */
    public static function getById($id) {
        $thumbnail = new self();
        $thumbnail->getResource()->getById($id);

        return $thumbnail;
    }

    /**
     * @param string $name
     * @return Thumbnail
     */
    public static function getByName($name) {
        $thumbnail = new self();
        $thumbnail->getResource()->getByName($name);

        return $thumbnail;
    }

    /**
     * Creates a hash
     *
     * @return string
     */
    public function getHash() {
        return md5(serialize(get_object_vars($this)));
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return integer
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @return integer
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @return boolean
     */
    public function getAspectratio() {
        return $this->aspectratio;
    }

    /**
     * @return boolean
     */
    public function getInterlace() {
        return $this->interlace;
    }

    /**
     * @return integer
     */
    public function getQuality() {
        return $this->quality;
    }

    /**
     * @return string
     */
    public function getFormat() {
        return $this->format;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setWidth($width) {
        $this->width = $width;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height) {
        $this->height = $height;
    }

    /**
     * @param boolean $aspectratio
     * @return void
     */
    public function setAspectratio($aspectratio) {
        $this->aspectratio = (bool) $aspectratio;
    }

    /**
     * @param boolean $interlace
     * @return void
     */
    public function setInterlace($interlace) {
        $this->interlace = (bool) $interlace;
    }

    /**
     * @param integer $quality
     * @return void
     */
    public function setQuality($quality) {
        $this->quality = $quality;
    }

    /**
     * @param string $format
     * @return void
     */
    public function setFormat($format) {
        $this->format = $format;
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
        $this->id = $id;
    }

    /**
     * @param boolean $contain
     * @return void
     */
    public function setContain($contain) {
        $this->contain = (bool) $contain;
    }

    /**
     * @param boolean $cover
     * @return void
     */
    public function setCover($cover) {
        $this->cover = (bool) $cover;
    }

    /**
     * @return boolean
     */
    public function getContain() {
        return $this->contain;
    }

    /**
     * @return boolean
     */
    public function getCover() {
        return $this->cover;
    }
}
