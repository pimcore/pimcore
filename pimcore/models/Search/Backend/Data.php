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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Search\Backend;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Model\Element;
use Pimcore\Logger;
use ForceUTF8\Encoding;

class Data extends \Pimcore\Model\AbstractModel
{

    /**
     * @var Data\Id
     */
    public $id;

    /**
     * @var string
     */
    public $fullPath;

    /**
     * document | object | asset
     * @var string
     */
    public $maintype;

    /**
     * webresource type (e.g. page, snippet ...)
     * @var string
     */
    public $type;

    /**
     * currently only relevant for objects where it portrays the class name
     * @var string
     */
    public $subtype;

    /**
     * published or not
     *
     * @var bool
     */
    public $published;

    /**
     * timestamp of creation date
     *
     * @var integer
     */
    public $creationDate;

    /**
     * timestamp of modification date
     *
     * @var integer
     */
    public $modificationDate;

    /**
     * User-ID of the owner
     *
     * @var integer
     */
    public $userOwner;

    /**
     * User-ID of the user last modified the element
     *
     * @var integer
     */
    public $userModification;

    /**
     * @var string
     */
    public $data;

    /**
     * @var string
     */
    public $properties;

    /**
     * @param null $element
     */
    public function __construct($element = null)
    {
        if ($element instanceof Element\ElementInterface) {
            $this->setDataFromElement($element);
        }
    }

    /**
     * @return \Pimcore\Model\Dao\AbstractDao
     * @throws \Exception
     */
    public function getDao()
    {
        if (!$this->dao) {
            $this->initDao("\\Pimcore\\Model\\Search\\Backend\\Data");
        }

        return $this->dao;
    }


    /**
     * @return Data\Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     * @param  string $fullpath
     * @return $this
     */
    public function setFullPath($fullpath)
    {
        $this->fullPath = $fullpath;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }


    /**
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * @param $subtype
     * @return $this
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;

        return $this;
    }

    /**
     * @return integer
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return integer
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param integer $modificationDate
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * @return integer
     */
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * @param integer $userModification
     * @return $this
     */
    public function setUserModification($userModification)
    {
        $this->userModification = $userModification;

        return $this;
    }

    /**
     * @return integer
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * @param integer $userOwner
     * @return $this
     */
    public function setUserOwner($userOwner)
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublished()
    {
        return (bool) $this->getPublished();
    }

    /**
     * @return boolean
     */
    public function getPublished()
    {
        return (bool) $this->published;
    }

    /**
     * @param integer $published
     * @return $this
     */
    public function setPublished($published)
    {
        $this->published = (bool) $published;

        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param  string $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
    * @return string
    */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param  string $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param $element
     * @return $this
     */
    public function setDataFromElement($element)
    {
        $this->data = null;

        $this->id = new Data\Id($element);
        $this->fullPath = $element->getRealFullPath();
        $this->creationDate=$element->getCreationDate();
        $this->modificationDate=$element->getModificationDate();
        $this->userModification = $element->getUserModification();
        $this->userOwner = $element->getUserOwner();

        $this->type = $element->getType();
        if ($element instanceof Object\Concrete) {
            $this->subtype = $element->getClassName();
        } else {
            $this->subtype = $this->type;
        }

        $this->properties = "";
        $properties = $element->getProperties();
        if (is_array($properties)) {
            foreach ($properties as $nextProperty) {
                $pData = (string) $nextProperty->getData();
                if ($nextProperty->getName() == "bool") {
                    $pData = $pData ? "true" : "false";
                }

                $this->properties .= $nextProperty->getName() . ":" . $pData ." ";
            }
        }

        $this->data = "";
        if ($element instanceof Document) {
            if ($element instanceof Document\Folder) {
                $this->data = $element->getKey();
                $this->published = true;
            } elseif ($element instanceof Document\Link) {
                $this->published = $element->isPublished();
                $this->data = $element->getTitle()." ".$element->getHref();
            } elseif ($element instanceof Document\PageSnippet) {
                $this->published = $element->isPublished();
                $elements = $element->getElements();
                if (is_array($elements) && !empty($elements)) {
                    foreach ($elements as $tag) {
                        if ($tag instanceof Document\Tag\TagInterface) {
                            ob_start();
                            $this->data .= strip_tags($tag->frontend())." ";
                            $this->data .= ob_get_clean();
                        }
                    }
                }
                if ($element instanceof Document\Page) {
                    $this->published = $element->isPublished();
                    $this->data .= " ".$element->getTitle()." ".$element->getDescription()." " . $element->getPrettyUrl();
                }
            }
        } elseif ($element instanceof Asset) {
            $this->data = $element->getFilename();

            $elementMetadata = $element->getMetadata();
            if (is_array($elementMetadata)) {
                foreach ($elementMetadata as $md) {
                    if (is_scalar($md['data'])) {
                        $this->data .= " " . $md["name"] . ":" . $md["data"];
                    }
                }
            }

            if ($element instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
                if (\Pimcore\Document::isFileTypeSupported($element->getFilename())) {
                    try {
                        $contentText = $element->getText();
                        $contentText = Encoding::toUTF8($contentText);
                        $contentText = str_replace(["\r\n", "\r", "\n", "\t", "\f"], " ", $contentText);
                        $contentText = preg_replace("/[ ]+/", " ", $contentText);
                        $this->data .= " " . $contentText;
                    } catch (\Exception $e) {
                        Logger::error($e);
                    }
                }
            } elseif ($element instanceof Asset\Text) {
                try {
                    $contentText = $element->getData();
                    $contentText = Encoding::toUTF8($contentText);
                    $this->data .= " " . $contentText;
                } catch (\Exception $e) {
                    Logger::error($e);
                }
            } elseif ($element instanceof Asset\Image) {
                try {
                    $metaData = array_merge($element->getEXIFData(), $element->getIPTCData());
                    foreach ($metaData as $key => $value) {
                        $this->data .= " " . $key . " : " . $value;
                    }
                } catch (\Exception $e) {
                    Logger::error($e);
                }
            }

            $this->published = true;
        } elseif ($element instanceof Object\AbstractObject) {
            if ($element instanceof Object\Concrete) {
                $getInheritedValues = Object\AbstractObject::doGetInheritedValues();
                Object\AbstractObject::setGetInheritedValues(true);

                $this->published = $element->isPublished();
                foreach ($element->getClass()->getFieldDefinitions() as $key => $value) {
                    $this->data .= $value->getDataForSearchIndex($element)." ";
                }

                Object\AbstractObject::setGetInheritedValues($getInheritedValues);
            } elseif ($element instanceof Object\Folder) {
                $this->data=$element->getKey();
                $this->published = true;
            }
        } else {
            Logger::crit("Search\\Backend\\Data received an unknown element!");
        }

        if ($element instanceof Element\ElementInterface) {
            $this->data = "ID: " . $element->getId() . "  \nPath: " . $this->getFullPath() . "  \n"  . $this->cleanupData($this->data);
        }

        return $this;
    }

    /**
     * @param $data
     * @return mixed|string
     */
    protected function cleanupData($data)
    {
        $data = strip_tags($data);

        $data = html_entity_decode($data, ENT_QUOTES, "UTF-8");

        $data = str_replace([".", ",", ":", ";", "'", '"'], " ", $data);
        $data = str_replace("\r\n", " ", $data);
        $data = str_replace("\n", " ", $data);
        $data = str_replace("\r", " ", $data);
        $data = str_replace("\t", "", $data);
        $data = preg_replace('#[ ]+#', ' ', $data);

        // deduplication
        $arr = explode(" ", $data);
        $arr = array_unique($arr);
        $data = implode(" ", $arr);

        return $data;
    }

    /**
     * @param $element
     * @return Data
     */
    public static function getForElement($element)
    {
        $data = new self();
        $data->getDao()->getForElement($element);

        return $data;
    }

    /**
     *
     */
    public function delete()
    {
        $this->getDao()->delete();
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if ($this->id instanceof Data\Id) {
            \Pimcore::getEventManager()->trigger("search.backend.preSave", $this);
            $this->getDao()->save();
            \Pimcore::getEventManager()->trigger("search.backend.postSave", $this);
        } else {
            throw new \Exception("Search\\Backend\\Data cannot be saved - no id set!");
        }
    }
}
