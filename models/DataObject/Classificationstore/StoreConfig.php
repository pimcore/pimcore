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
use Pimcore\Event\Model\DataObject\ClassificationStore\StoreConfigEvent;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\StoreConfig\Dao getDao()
 */
final class StoreConfig extends Model\AbstractModel
{
    /**
     * @var int
     */
    protected $id;

    /**
     * The store name.
     *
     * @var string
     */
    protected $name;

    /**
     * The store description.
     *
     * @var string
     */
    protected $description;

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
     *
     * @return self|null
     *
     * @throws \Exception
     */
    public static function getByName($name)
    {
        try {
            $config = new self();
            $config->getDao()->getByName($name);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @return Model\DataObject\Classificationstore\StoreConfig
     */
    public static function create()
    {
        $config = new self();
        $config->save();

        return $config;
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
     * @return Model\DataObject\Classificationstore\StoreConfig
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
        \Pimcore::getEventDispatcher()->dispatch(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_DELETE);
        $this->getDao()->delete();
        \Pimcore::getEventDispatcher()->dispatch(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_POST_DELETE);
    }

    /**
     * Saves the store config
     */
    public function save()
    {
        $isUpdate = false;

        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventDispatcher()->dispatch(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_ADD);
        }

        $model = $this->getDao()->save();

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_POST_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_POST_ADD);
        }

        return $model;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
