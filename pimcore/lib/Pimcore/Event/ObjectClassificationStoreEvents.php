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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event;

final class ObjectClassificationStoreEvents
{
    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\CollectionConfigEvent")
     * @var string
     */
    const COLLECTION_CONFIG_PRE_ADD = 'pimcore.object.classificationstore.collectionConfig.preAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\CollectionConfigEvent")
     * @var string
     */
    const COLLECTION_CONFIG_POST_ADD = 'pimcore.object.classificationstore.collectionConfig.postAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\CollectionConfigEvent")
     * @var string
     */
    const COLLECTION_CONFIG_PRE_UPDATE = 'pimcore.object.classificationstore.collectionConfig.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\CollectionConfigEvent")
     * @var string
     */
    const COLLECTION_CONFIG_POST_UPDATE = 'pimcore.object.classificationstore.collectionConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\CollectionConfigEvent")
     * @var string
     */
    const COLLECTION_CONFIG_PRE_DELETE = 'pimcore.object.classificationstore.collectionConfig.preDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\CollectionConfigEvent")
     * @var string
     */
    const COLLECTION_CONFIG_POST_DELETE = 'pimcore.object.classificationstore.collectionConfig.postDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\GroupConfigEvent")
     * @var string
     */
    const GROUP_CONFIG_PRE_ADD = 'pimcore.object.classificationstore.groupConfig.preAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\GroupConfigEvent")
     * @var string
     */
    const GROUP_CONFIG_POST_ADD = 'pimcore.object.classificationstore.groupConfig.postAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\GroupConfigEvent")
     * @var string
     */
    const GROUP_CONFIG_PRE_UPDATE = 'pimcore.object.classificationstore.groupConfig.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\GroupConfigEvent")
     * @var string
     */
    const GROUP_CONFIG_POST_UPDATE = 'pimcore.object.classificationstore.groupConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\GroupConfigEvent")
     * @var string
     */
    const GROUP_CONFIG_PRE_DELETE = 'pimcore.object.classificationstore.groupConfig.preDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\GroupConfigEvent")
     * @var string
     */
    const GROUP_CONFIG_POST_DELETE = 'pimcore.object.classificationstore.groupConfig.postDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\KeyConfigEvent")
     * @var string
     */
    const KEY_CONFIG_PRE_ADD = 'pimcore.object.classificationstore.keyConfig.preAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\KeyConfigEvent")
     * @var string
     */
    const KEY_CONFIG_POST_ADD = 'pimcore.object.classificationstore.keyConfig.postAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\KeyConfigEvent")
     * @var string
     */
    const KEY_CONFIG_PRE_UPDATE = 'pimcore.object.classificationstore.keyConfig.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\KeyConfigEvent")
     * @var string
     */
    const KEY_CONFIG_POST_UPDATE = 'pimcore.object.classificationstore.keyConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\KeyConfigEvent")
     * @var string
     */
    const KEY_CONFIG_PRE_DELETE = 'pimcore.object.classificationstore.keyConfig.preDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\KeyConfigEvent")
     * @var string
     */
    const KEY_CONFIG_POST_DELETE = 'pimcore.object.classificationstore.keyConfig.postDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\StoreConfigEvent")
     * @var string
     */
    const STORE_CONFIG_PRE_ADD = 'pimcore.object.classificationstore.storeConfig.preAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\StoreConfigEvent")
     * @var string
     */
    const STORE_CONFIG_POST_ADD = 'pimcore.object.classificationstore.storeConfig.postAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\StoreConfigEvent")
     * @var string
     */
    const STORE_CONFIG_PRE_UPDATE = 'pimcore.object.classificationstore.storeConfig.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\StoreConfigEvent")
     * @var string
     */
    const STORE_CONFIG_POST_UPDATE = 'pimcore.object.classificationstore.storeConfig.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\StoreConfigEvent")
     * @var string
     */
    const STORE_CONFIG_PRE_DELETE = 'pimcore.object.classificationstore.storeConfig.preDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassificationStore\StoreConfigEvent")
     * @var string
     */
    const STORE_CONFIG_POST_DELETE = 'pimcore.object.classificationstore.storeConfig.postDelete';
}
