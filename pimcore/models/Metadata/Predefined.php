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
 * @package    Metadata
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Metadata;

use Pimcore\Model;
use Pimcore\Model\Element;

/**
 * @method \Pimcore\Model\Metadata\Predefined\Dao getDao()
 */
class Predefined extends Model\AbstractModel
{

    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $targetSubtype;


    /**
     * @var string
     */
    public $data;

    /**
     * @var string
     */
    public $config;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var string
     */
    public $language;

    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;



    /**
     * @param integer $id
     * @return self
     */
    public static function getById($id)
    {
        try {
            $metadata = new self();
            $metadata->setId($id);
            $metadata->getDao()->getById();

            return $metadata;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $key
     * @return self
     */
    public static function getByName($name, $language = "")
    {
        try {
            $metadata = new self();
            $metadata->setName($name);
            $metadata->getDao()->getByNameAndLanguage($name, $language);

            return $metadata;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return self
     */
    public static function create()
    {
        $type = new self();
        $type->save();

        return $type;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }


    /**
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param $modificationDate
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $targetSubtype
     */
    public function setTargetSubtype($targetSubtype)
    {
        $this->targetSubtype = $targetSubtype;
    }

    /**
     * @return string
     */
    public function getTargetSubtype()
    {
        return $this->targetSubtype;
    }

    /**
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }


    /**
     *
     */
    public function minimize()
    {
        switch ($this->type) {
            case "document":
            case "asset":
            case "object":
                {
                    $element = Element\Service::getElementByPath($this->type, $this->data);
                    if ($element) {
                        $this->data = $element->getId();
                    } else {
                        $this->data = "";
                    }
                }
                break;
            case "date":
            {
                if ($this->data && !is_numeric($this->data)) {
                    $this->data = strtotime($this->data);
                }
            }
            default:
                //nothing to do
        }
    }

    /**
     *
     */
    public function expand()
    {
        switch ($this->type) {
            case "document":
            case "asset":
            case "object":
                {
                if (is_numeric($this->data)) {
                    $element = Element\Service::getElementById($this->type, $this->data);
                }
                if ($element) {
                    $this->data = $element->getRealFullPath();
                } else {
                    $this->data = "";
                }
            }

            break;
            default:
        //nothing to do
        }
    }
}
