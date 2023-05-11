<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Property;

use Pimcore\Model;

/**
 * @internal
 *
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method \Pimcore\Model\Property\Predefined\Dao getDao()
 * @method void delete()
 * @method void save()
 */
final class Predefined extends Model\AbstractModel
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $key;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var string
     */
    protected $config;

    /**
     * @var string
     */
    protected $ctype;

    /**
     * @var bool
     */
    protected $inheritable = false;

    /**
     * @var int|null
     */
    protected $creationDate;

    /**
     * @var int|null
     */
    protected $modificationDate;

    /**
     * @param string $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        try {
            $property = new self();
            $property->getDao()->getById($id);

            return $property;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param string $key
     *
     * @return self|null
     */
    public static function getByKey($key)
    {
        $cacheKey = 'property_predefined_' . $key;

        try {
            $property = \Pimcore\Cache\RuntimeCache::get($cacheKey);
            if (!$property) {
                throw new \Exception('Predefined property in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $property = new self();
                $property->getDao()->getByKey($key);
                \Pimcore\Cache\RuntimeCache::set($cacheKey, $property);
            } catch (Model\Exception\NotFoundException $e) {
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
     * @return string|null
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
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $data
     *
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
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
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $config
     *
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
     *
     * @return $this
     */
    public function setCtype($ctype)
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @return bool
     */
    public function getInheritable()
    {
        return $this->inheritable;
    }

    /**
     * @param bool $inheritable
     *
     * @return $this
     */
    public function setInheritable($inheritable)
    {
        $this->inheritable = (bool) $inheritable;

        return $this;
    }

    /**
     * @param string $description
     *
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
     *
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    public function __clone()
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
