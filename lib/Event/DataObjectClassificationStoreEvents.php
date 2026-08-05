<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Event;

final class DataObjectClassificationStoreEvents
{
    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     *
     * @var string
     */
    const COLLECTION_CONFIG_PRE_ADD = 'pimcore.dataobject.classificationstore.collectionConfig.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     *
     * @var string
     */
    const COLLECTION_CONFIG_POST_ADD = 'pimcore.dataobject.classificationstore.collectionConfig.postAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     *
     * @var string
     */
    const COLLECTION_CONFIG_PRE_UPDATE = 'pimcore.dataobject.classificationstore.collectionConfig.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     *
     * @var string
     */
    const COLLECTION_CONFIG_POST_UPDATE = 'pimcore.dataobject.classificationstore.collectionConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     *
     * @var string
     */
    const COLLECTION_CONFIG_PRE_DELETE = 'pimcore.dataobject.classificationstore.collectionConfig.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     *
     * @var string
     */
    const COLLECTION_CONFIG_POST_DELETE = 'pimcore.dataobject.classificationstore.collectionConfig.postDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     *
     * @var string
     */
    const GROUP_CONFIG_PRE_ADD = 'pimcore.dataobject.classificationstore.groupConfig.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     *
     * @var string
     */
    const GROUP_CONFIG_POST_ADD = 'pimcore.dataobject.classificationstore.groupConfig.postAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     *
     * @var string
     */
    const GROUP_CONFIG_PRE_UPDATE = 'pimcore.dataobject.classificationstore.groupConfig.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     *
     * @var string
     */
    const GROUP_CONFIG_POST_UPDATE = 'pimcore.dataobject.classificationstore.groupConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     *
     * @var string
     */
    const GROUP_CONFIG_PRE_DELETE = 'pimcore.dataobject.classificationstore.groupConfig.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     *
     * @var string
     */
    const GROUP_CONFIG_POST_DELETE = 'pimcore.dataobject.classificationstore.groupConfig.postDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     *
     * @var string
     */
    const KEY_CONFIG_PRE_ADD = 'pimcore.dataobject.classificationstore.keyConfig.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     *
     * @var string
     */
    const KEY_CONFIG_POST_ADD = 'pimcore.dataobject.classificationstore.keyConfig.postAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     *
     * @var string
     */
    const KEY_CONFIG_PRE_UPDATE = 'pimcore.dataobject.classificationstore.keyConfig.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     *
     * @var string
     */
    const KEY_CONFIG_POST_UPDATE = 'pimcore.dataobject.classificationstore.keyConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     *
     * @var string
     */
    const KEY_CONFIG_PRE_DELETE = 'pimcore.dataobject.classificationstore.keyConfig.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     *
     * @var string
     */
    const KEY_CONFIG_POST_DELETE = 'pimcore.dataobject.classificationstore.keyConfig.postDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     *
     * @var string
     */
    const STORE_CONFIG_PRE_ADD = 'pimcore.dataobject.classificationstore.storeConfig.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     *
     * @var string
     */
    const STORE_CONFIG_POST_ADD = 'pimcore.dataobject.classificationstore.storeConfig.postAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     *
     * @var string
     */
    const STORE_CONFIG_PRE_UPDATE = 'pimcore.dataobject.classificationstore.storeConfig.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     *
     * @var string
     */
    const STORE_CONFIG_POST_UPDATE = 'pimcore.dataobject.classificationstore.storeConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     *
     * @var string
     */
    const STORE_CONFIG_PRE_DELETE = 'pimcore.dataobject.classificationstore.storeConfig.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     *
     * @var string
     */
    const STORE_CONFIG_POST_DELETE = 'pimcore.dataobject.classificationstore.storeConfig.postDelete';
}
