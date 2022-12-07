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

namespace Pimcore\Model\Metadata;

use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Logger;
use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Metadata\Predefined\Dao getDao()
 * @method void save()
 * @method void delete()
 * @method bool isWriteable()
 * @method string getWriteTarget()
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
     * @var string|null
     */
    protected $description;

    /**
     * @TODO if required?
     *
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string|null
     */
    protected $targetSubtype;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var string|null
     */
    protected $config;

    /**
     * @TODO if required?
     *
     * @var string
     */
    protected $ctype;

    /**
     * @var string|null
     */
    protected $language;

    /**
     * @var string|null
     */
    protected $group;

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
            $metadata = new self();
            $metadata->getDao()->getById($id);

            return $metadata;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param string $name
     * @param string $language
     *
     * @return self|null
     *
     * @throws \Exception
     */
    public static function getByName($name, $language = '')
    {
        try {
            $metadata = new self();
            $metadata->setName($name);
            $metadata->getDao()->getByNameAndLanguage($name, $language);

            return $metadata;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @return self
     */
    public static function create()
    {
        $type = new self();

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
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = str_replace('~', '---', $name);

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
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
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

    /**
     * @param string|null $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string|null $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return string|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string|null $targetSubtype
     */
    public function setTargetSubtype($targetSubtype)
    {
        $this->targetSubtype = $targetSubtype;
    }

    /**
     * @return string|null
     */
    public function getTargetSubtype()
    {
        return $this->targetSubtype;
    }

    /**
     * @return string|null
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string|null $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function minimize()
    {
        try {
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            /** @var Model\Asset\MetaData\ClassDefinition\Data\Data $instance */
            $instance = $loader->build($this->type);
            $this->data = $instance->getDataFromEditMode($this->data);
        } catch (UnsupportedException $e) {
            Logger::error('could not resolve asset metadata implementation for ' . $this->type);
        }
    }

    public function expand()
    {
        try {
            $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
            /** @var Model\Asset\MetaData\ClassDefinition\Data\Data $instance */
            $instance = $loader->build($this->type);
            $this->data = $instance->getDataForEditmode($this->data);
        } catch (UnsupportedException $e) {
            Logger::error('could not resolve asset metadata implementation for ' . $this->type);
        }
    }

    public function __clone()
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
