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
 * @package    Property
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Property;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Property\Predefined\Dao getDao()
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
     * @var bool
     */
    public $inheritable = false;

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
            $property = new self();
            $property->setId($id);
            $property->getDao()->getById();
        } catch (\Exception $e) {
            return null;
        }

        return $property;
    }

    /**
     * @param string $key
     * @return self
     */
    public static function getByKey($key)
    {
        $cacheKey = "property_predefined_" . $key;

        try {
            $property = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$property) {
                throw new \Exception("Predefined property in registry is null");
            }
        } catch (\Exception $e) {
            try {
                $property = new self();
                $property->setKey($key);
                $property->getDao()->getByKey();

                \Pimcore\Cache\Runtime::set($cacheKey, $property);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $property;
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
    public function getKey()
    {
        return $this->key;
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
     * @param string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
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
     * @return $this
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
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
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
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return string
     */
    public function getCtype()
    {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     * @return $this
     */
    public function setCtype($ctype)
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @return string
     */
    public function getInheritable()
    {
        return (bool) $this->inheritable;
    }

    /**
     * @param string $inheritable
     * @return $this
     */
    public function setInheritable($inheritable)
    {
        $this->inheritable = (bool) $inheritable;

        return $this;
    }

    /**
     * @param string $description
     * @return $this
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
     * @param int $creationDate
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
     * @param int $modificationDate
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
}
