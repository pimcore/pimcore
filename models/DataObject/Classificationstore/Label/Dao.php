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

namespace Pimcore\Model\DataObject\Classificationstore\Label;

use Pimcore;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Dao as CollectionConfigDao;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao as KeyConfigDao;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolver;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property Classificationstore\Label $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public const TABLE_NAME_LABELS = 'classificationstore_labels';

    /**
     * The configuration tables whose rows a store scopes, by the entity type of their labels.
     */
    private const STORE_SCOPED_TABLES = [
        LabelEntityType::Collection->value => CollectionConfigDao::TABLE_NAME_COLLECTIONS,
        LabelEntityType::Group->value => GroupConfigDao::TABLE_NAME_GROUPS,
        LabelEntityType::Key->value => KeyConfigDao::TABLE_NAME_KEYS,
    ];

    /**
     * @throws NotFoundException
     */
    public function getByEntity(LabelEntityType $entityType, int $entityId, string $language): void
    {
        $data = $this->db->fetchAssociative(
            'SELECT * FROM `' . self::TABLE_NAME_LABELS . '`'
            . ' WHERE `entityType` = ? AND `entityId` = ? AND `language` = ?',
            [$entityType->value, $entityId, $language]
        );

        if (!$data) {
            throw new NotFoundException(sprintf(
                'Classification store label of %s %d in language "%s" does not exist.',
                $entityType->value,
                $entityId,
                $language
            ));
        }

        $this->assignRowToModel($data);
    }

    /**
     * Hydrates the model from a row of the label table, for this DAO and for the listing alike.
     *
     * @param array<string, mixed> $row
     */
    public function assignRowToModel(array $row): void
    {
        $this->assignVariablesToModel([
            'entityType' => LabelEntityType::from((string) $row['entityType']),
            'entityId' => (int) $row['entityId'],
            'language' => (string) $row['language'],
            'title' => $row['title'] === null ? null : (string) $row['title'],
            'description' => $row['description'] === null ? null : (string) $row['description'],
        ]);
    }

    public function save(): void
    {
        $this->update();
    }

    public function update(): void
    {
        Helper::upsert(
            $this->db,
            self::TABLE_NAME_LABELS,
            [
                'entityType' => $this->model->getEntityType()->value,
                'entityId' => $this->model->getEntityId(),
                'language' => $this->model->getLanguage(),
                'title' => $this->model->getTitle(),
                'description' => $this->model->getDescription(),
            ],
            $this->getPrimaryKey(self::TABLE_NAME_LABELS)
        );

        $this->invalidateResolvedLabels();
    }

    /**
     * Deletes the label of one entity in one language.
     */
    public function delete(): void
    {
        $this->db->delete(self::TABLE_NAME_LABELS, [
            'entityType' => $this->model->getEntityType()->value,
            'entityId' => $this->model->getEntityId(),
            'language' => $this->model->getLanguage(),
        ]);

        $this->invalidateResolvedLabels();
    }

    /**
     * Deletes the labels of the addressed entity in every language, for the deletion of that entity.
     */
    public function deleteByEntity(): void
    {
        $this->db->delete(self::TABLE_NAME_LABELS, [
            'entityType' => $this->model->getEntityType()->value,
            'entityId' => $this->model->getEntityId(),
        ]);

        $this->invalidateResolvedLabels();
    }

    /**
     * Deletes the labels of a store and of its collections, groups and keys.
     *
     * To be called before those configuration rows are deleted: the scope is read from them, so
     * afterwards there is nothing left to scope by.
     */
    public function deleteByStore(int $storeId): void
    {
        foreach (self::STORE_SCOPED_TABLES as $entityType => $table) {
            $this->db->executeStatement(
                'DELETE FROM `' . self::TABLE_NAME_LABELS . '` WHERE `entityType` = ?'
                . ' AND `entityId` IN (SELECT `id` FROM `' . $table . '` WHERE `storeId` = ?)',
                [$entityType, $storeId]
            );
        }

        $this->db->delete(self::TABLE_NAME_LABELS, [
            'entityType' => LabelEntityType::Store->value,
            'entityId' => $storeId,
        ]);

        Pimcore::getContainer()->get(LabelResolver::class)->reset();
    }

    /**
     * A label dispatches no event, so the request-scoped resolver is invalidated here - which
     * covers every caller that goes through the model.
     */
    private function invalidateResolvedLabels(): void
    {
        Pimcore::getContainer()
            ->get(LabelResolver::class)
            ->invalidate($this->model->getEntityType(), $this->model->getEntityId());
    }
}
