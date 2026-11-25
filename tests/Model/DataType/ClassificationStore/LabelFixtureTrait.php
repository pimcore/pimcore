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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\Label;
use Pimcore\Model\DataObject\Classificationstore\Label\Dao as LabelDao;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolver;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * The configuration entities and label rows the label tests write, and their cleanup.
 *
 * @internal
 */
trait LabelFixtureTrait
{
    use ClassificationStoreAutoIncrementTrait;

    private const STORE_SCOPED_TABLES = [
        'classificationstore_collections',
        'classificationstore_groups',
        'classificationstore_keys',
    ];

    /**
     * @var int[]
     */
    private array $createdStoreIds = [];

    protected function writeLabel(
        LabelEntityType $entityType,
        int $entityId,
        string $language,
        ?string $title,
        ?string $description
    ): void {
        $label = new Label();
        $label->setEntityType($entityType);
        $label->setEntityId($entityId);
        $label->setLanguage($language);
        $label->setTitle($title);
        $label->setDescription($description);
        $label->save();
    }

    protected function countLabels(LabelEntityType $entityType, int $entityId): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM `' . LabelDao::TABLE_NAME_LABELS . '` WHERE `entityType` = ? AND `entityId` = ?',
            [$entityType->value, $entityId]
        );
    }

    protected function createStore(): StoreConfig
    {
        $store = new StoreConfig();
        $store->setName(uniqid('labelstore', true));
        $store->save();

        $this->createdStoreIds[] = $store->getId();

        return $store;
    }

    protected function createEntity(
        LabelEntityType $entityType,
        ?int $storeId = null
    ): StoreConfig|CollectionConfig|GroupConfig|KeyConfig {
        if ($entityType === LabelEntityType::Store) {
            return $this->createStore();
        }

        $entity = match ($entityType) {
            LabelEntityType::Collection => new CollectionConfig(),
            LabelEntityType::Group => new GroupConfig(),
            default => new KeyConfig(),
        };

        if ($entity instanceof KeyConfig) {
            $entity->setType('input');
        }

        $entity->setStoreId($storeId ?? $this->createStore()->getId());
        $entity->setName(uniqid('label' . $entityType->value, true));
        $entity->save();

        return $entity;
    }

    /**
     * Removes every label row and every configuration entity the test created, and puts the
     * auto-increment counters back where the shared fixtures expect them.
     */
    protected function deleteLabelFixtures(): void
    {
        $db = Db::get();

        foreach ($this->createdStoreIds as $storeId) {
            foreach (self::STORE_SCOPED_TABLES as $table) {
                $db->delete($table, ['storeId' => $storeId]);
            }
            $db->delete('classificationstore_stores', ['id' => $storeId]);
        }

        $db->executeStatement('DELETE FROM `' . LabelDao::TABLE_NAME_LABELS . '`');

        $this->createdStoreIds = [];
        $this->restoreClassificationStoreAutoIncrement();

        Pimcore::getContainer()->get(LabelResolver::class)->reset();
    }
}
