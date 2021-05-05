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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Event\DataObjectClassificationStoreEvents;
use Pimcore\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Dao getDao()
 */
final class CollectionConfig extends Model\AbstractModel
{
    /**
     * Group id.
     *
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $storeId = 1;

    /**
     * @var string
     */
    protected $name;

    /**
     * The collection description.
     *
     * @var string
     */
    protected $description;

    /**
     * @var int
     */
    protected $creationDate;

    /**
     * @var int
     */
    protected $modificationDate;

    /**
     * @param int $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        try {
            $config = new self();
            $config->getDao()->getById((int)$id);

            return $config;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $name
     * @param int $storeId
     *
     * @return self|null
     *
     * @throws \Exception
     */
    public static function getByName($name, $storeId = 1)
    {
        try {
            $config = new self();
            $config->setName($name);
            $config->setStoreId($storeId ? $storeId : 1);
            $config->getDao()->getByName();

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @return Model\DataObject\Classificationstore\CollectionConfig
     */
    public static function create()
    {
        $config = new self();
        $config->save();

        return $config;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     * @param string $description
     *
     * @return Model\DataObject\Classificationstore\CollectionConfig
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Deletes the key value group configuration
     */
    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_PRE_DELETE);
        $this->getDao()->delete();
        \Pimcore::getEventDispatcher()->dispatch(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_POST_DELETE);
    }

    /**
     * Saves the collection config
     */
    public function save()
    {
        $isUpdate = false;

        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventDispatcher()->dispatch(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_PRE_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_PRE_ADD);
        }

        $model = $this->getDao()->save();

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_POST_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new CollectionConfigEvent($this), DataObjectClassificationStoreEvents::COLLECTION_CONFIG_POST_ADD);
        }

        return $model;
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
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
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
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }
}
