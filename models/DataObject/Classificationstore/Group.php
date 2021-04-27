<?php

declare(strict_types=1);

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

use Pimcore\Model\DataObject\Classificationstore;

final class Group
{
    /**
     * @var GroupConfig
     */
    protected $configuration;

    /**
     * @var Classificationstore
     */
    protected $classificationStore;

    /**
     * @param Classificationstore $classificationStore
     * @param GroupConfig $configuration
     */
    public function __construct(Classificationstore $classificationStore, GroupConfig $configuration)
    {
        $this->configuration = $configuration;
        $this->classificationStore = $classificationStore;
    }

    /**
     * @return GroupConfig
     */
    public function getConfiguration(): GroupConfig
    {
        return $this->configuration;
    }

    /**
     * @return Classificationstore
     */
    public function getClassificationStore(): Classificationstore
    {
        return $this->classificationStore;
    }

    /**
     * @return Key[]
     */
    public function getKeys(): array
    {
        return $this->getKeysByKeyGroupRelations(
            ...$this->getKeyGroupRelations()
        );
    }

    /**
     * @return KeyGroupRelation[]
     */
    protected function getKeyGroupRelations(): array
    {
        return $this->getKeyGroupRelationListing()
            ->setCondition('groupId = ' . $this->configuration->getId())
            ->setOrderKey([
                'sorter',
                'keyId',
            ])
            ->load();
    }

    /**
     * @return KeyGroupRelation\Listing
     */
    protected function getKeyGroupRelationListing(): KeyGroupRelation\Listing
    {
        return new KeyGroupRelation\Listing();
    }

    /**
     * @param KeyGroupRelation ...$keyGroupRelations
     *
     * @return Key[]
     */
    protected function getKeysByKeyGroupRelations(KeyGroupRelation ...$keyGroupRelations): array
    {
        return array_map([$this, 'getKeyByKeyGroupRelation'], $keyGroupRelations);
    }

    /**
     * @param KeyGroupRelation $keyGroupRelation
     *
     * @return Key
     */
    protected function getKeyByKeyGroupRelation(KeyGroupRelation $keyGroupRelation): Key
    {
        $keyConfig = $this->getKeyConfigById((int)$keyGroupRelation->getKeyId());

        return new Key($this, $keyConfig);
    }

    /**
     * @param int $id
     *
     * @return KeyConfig
     */
    public function getKeyConfigById(int $id): KeyConfig
    {
        return KeyConfig::getById($id);
    }
}
