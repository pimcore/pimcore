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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Element;
use Pimcore\Tool\Text;

class Wysiwyg extends Model\Object\ClassDefinition\Data {

    use Model\Object\ClassDefinition\Data\Extension\Text;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "wysiwyg";

    /**
     * @var integer
     */
    public $width;

    /**
     * @var integer
     */
    public $height;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "longtext";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "longtext";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "string";

    /**
     * @var string
     */
    public $toolbarConfig = "";

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
     * @param integer $width
     * @return void
     */
    public function setWidth($width) {
        $this->width = $this->getAsIntegerCast($width);
        return $this;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height) {
        $this->height = $this->getAsIntegerCast($height);
        return $this;
    }

    /**
     * @param string $toolbarConfig
     */
    public function setToolbarConfig($toolbarConfig)
    {
        if(is_string($toolbarConfig)) {
            $this->toolbarConfig = $toolbarConfig;
        } else {
            $this->toolbarConfig = "";
        }
    }

    /**
     * @return string
     */
    public function getToolbarConfig()
    {
        return $this->toolbarConfig;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        return Text::wysiwygText($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @return string
     */
    public function getDataFromResource($data) {
        return Text::wysiwygText($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {

        $data = $this->getDataForResource($data);

        $data = strip_tags($data, "<a><img>");
        $data = str_replace("\r\n", " ", $data);
        $data = str_replace("\n", " ", $data);
        $data = str_replace("\r", " ", $data);
        $data = str_replace("\t", "", $data);
        $data = preg_replace ('#[ ]+#', ' ', $data);

        return $data;
    }


    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {
        return $data;
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies($data) {
        return Text::getDependenciesOfWysiwygText($data);
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags($data, $tags = array()) {
        return Text::getCacheTagsOfWysiwygText($data, $tags);
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
           throw new \Exception("Empty mandatory field [ ".$this->getName()." ]");
        }
        $dependencies = Text::getDependenciesOfWysiwygText($data);
        if (is_array($dependencies)) {
            foreach ($dependencies as $key => $value) {
                $el = Element\Service::getElementById($value['type'], $value['id']);
                if (!$el) {              
                    throw new \Exception("invalid dependency in wysiwyg text");
                }
            }
        }
    }

    /**
     * @param Object\Concrete $object
     * @return string
     */
    public function preGetData ($object, $params = array()) {

        $data = "";
        if($object instanceof Object\Concrete) {
            $data = $object->{$this->getName()};
        } else if ($object instanceof Object\Localizedfield) {
            $data = $params["data"];
        } else if ($object instanceof Object\Fieldcollection\Data\AbstractData) {
            $data = $object->{$this->getName()};
        } else if ($object instanceof Object\Objectbrick\Data\AbstractData) {
            $data = $object->{$this->getName()};
        }

        return Text::wysiwygText($data);
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null) {
        if ($data) {
            $value = array();
            $value["html"] = $data;
            $value["type"] = "html";
            return $value;
        } else {
            return "";
        }
    }


    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     * @return Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = array()) {

        include_once("simple_html_dom.php");

        $data = $this->getDataFromObjectParam($object, $params);
        $html = str_get_html($data);
        if($html) {
            $s = $html->find("a[pimcore_id],img[pimcore_id]");

            if($s) {
                foreach ($s as $el) {
                    if ($el->href || $el->src) {
                        $type = $el->pimcore_type;
                        $id = (int) $el->pimcore_id;

                        if(array_key_exists($type, $idMapping)) {
                            if(array_key_exists($id, $idMapping[$type])) {
                                $el->outertext = str_replace('="' . $el->pimcore_id . '"', '="' . $idMapping[$type][$id] . '"', $el->outertext);
                            }
                        }
                    }
                }
            }

            $data = $html->save();

            $html->clear();
            unset($html);
        }

        return $data;
    }
}
