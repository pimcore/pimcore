<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Classificationstore\KeyConfig;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\Traits\CustomMetadataDaoTrait;
use Pimcore\Model\DataObject\Classificationstore\Traits\IdentifierDaoTrait;
use Pimcore\Model\DataObject\Classificationstore\Traits\LabelCleanupDaoTrait;
use Throwable;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\KeyConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use CustomMetadataDaoTrait;
    use IdentifierDaoTrait;
    use LabelCleanupDaoTrait;

    const TABLE_NAME_KEYS = 'classificationstore_keys';

    private const COLUMN_LOCALIZED = 'localized';

    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(?int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_KEYS . ' WHERE id = ?', [$this->model->getId()]);

        if ($data) {
            $this->assignVariablesToModel($this->castRow(static::hydrateCustomMetadata($this->model, $data)));
        } else {
            throw new Model\Exception\NotFoundException('KeyConfig with id: ' . $this->model->getId() . ' does not exist');
        }
    }

    /**
     *
     * @throws Exception
     */
    public function getByName(?string $name = null): void
    {
        if ($name != null) {
            $this->model->setName($name);
        }

        $name = $this->model->getName();
        $storeId = $this->model->getStoreId();

        $stmt = 'SELECT * FROM ' . self::TABLE_NAME_KEYS . ' WHERE name = ' . $this->db->quote($name) . ' and storeId = ' . $storeId;

        $data = $this->db->fetchAssociative($stmt);

        if ($data) {
            $this->assignVariablesToModel($this->castRow(static::hydrateCustomMetadata($this->model, $data)));
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store key config with name "%s" does not exist.', $name));
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByIdentifier(string $identifier): void
    {
        $data = $this->db->fetchAssociative(
            'SELECT * FROM ' . self::TABLE_NAME_KEYS . ' WHERE identifier = ? AND storeId = ?',
            [$identifier, $this->model->getStoreId()]
        );

        if ($data) {
            $this->assignVariablesToModel($this->castRow(static::hydrateCustomMetadata($this->model, $data)));
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store key config with identifier "%s" does not exist.', $identifier));
        }
    }

    /**
     * @throws Model\Exception\InvalidConfigException
     */
    public function assertIdentifierIsUnique(): void
    {
        $identifier = $this->model->getIdentifier();
        if ($identifier === null) {
            return;
        }

        $used = $this->db->fetchOne(
            'SELECT id FROM ' . self::TABLE_NAME_KEYS . ' WHERE identifier = ? AND storeId = ? AND id != ?',
            [$identifier, $this->model->getStoreId(), $this->model->getId() ?? 0]
        );

        if ($used !== false) {
            throw new Model\Exception\InvalidConfigException(sprintf('The identifier "%s" is already in use.', $identifier));
        }
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->createAndUpdate();

            return;
        }

        $this->update();
    }

    /**
     * Deletes object from database, together with this key's own labels
     *
     * @throws Throwable
     */
    public function delete(): void
    {
        $this->deleteWithLabels(self::TABLE_NAME_KEYS, LabelEntityType::Key);
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        // the group relations deleted on the transition to disabled, the row itself and the identifier change as one unit
        $this->writeAtomically(function (): void {
            $id = $this->model->getId();
            $identifier = $this->model->getIdentifier();
            $identifierPending = $this->isIdentifierPending(self::TABLE_NAME_KEYS, $id, $identifier);

            $ts = time();
            $this->model->setModificationDate($ts);

            $data = [];
            $type = $this->model->getObjectVars();

            foreach ($type as $key => $value) {
                // a key built with `new` carries no title of its own, and must not clear a stored one
                if ($key === 'title' && !$this->model->isTitleInitialized() && !$this->model->isTitleSeeded()) {
                    continue;
                }

                // a key built with `new` carries no localization of its own either, and a stored
                // flag must survive a save that never decided about it
                if ($key === self::COLUMN_LOCALIZED && !$this->model->isLocalizedInitialized()) {
                    continue;
                }

                // written by writeIdentifier(), which is the only place allowed to touch the column
                if ($key === 'identifier') {
                    continue;
                }

                if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_KEYS))) {
                    if ($key === self::COLUMN_CUSTOM_METADATA) {
                        // JSON text, not a serialized payload; written only once loaded or explicitly set
                        if ($this->model->isCustomMetadataInitialized()) {
                            $data[$key] = static::encodeCustomMetadata($value);
                        }

                        continue;
                    }

                    if (is_bool($value)) {
                        $value = (int) $value;

                        if (!$value && $key === 'enabled' && $this->isStoredEnabledForUpdate((int) $this->model->getId())) {
                            $this->db->delete(
                                Model\DataObject\Classificationstore\KeyGroupRelation\Dao::TABLE_NAME_RELATIONS,
                                ['keyId' => $this->model->getId()]);
                        }
                    }
                    if (is_array($value) || is_object($value)) {
                        if ($this->model->getType() == 'select') {
                            $value = json_encode($value);
                        } else {
                            $value = \Pimcore\Tool\Serialize::serialize($value);
                        }
                    }

                    $data[$key] = $value;
                }
            }

            $this->updateRow(self::TABLE_NAME_KEYS, $data, (int) $this->model->getId());

            if ($identifierPending) {
                $this->writeIdentifier(self::TABLE_NAME_KEYS, (int) $id, (string) $identifier);
            }
        });
    }

    /**
     * A current read, so a transition another request has just committed is not missed; NULL counts as disabled.
     */
    private function isStoredEnabledForUpdate(int $id): bool
    {
        return (bool) $this->db->fetchOne(
            'SELECT `enabled` FROM `' . self::TABLE_NAME_KEYS . '` WHERE `id` = ? FOR UPDATE',
            [$id]
        );
    }

    public function create(): void
    {
        $ts = time();
        $this->model->setCreationDate($ts);
        $this->model->setModificationDate($ts);

        $this->db->insert(self::TABLE_NAME_KEYS, []);

        $this->model->setId((int) $this->db->lastInsertId());
    }

    /**
     * The row as the model takes it. The tri-state `localized` column is left as NULL, which
     * {@see Model\AbstractModel::setValues()} skips, so the property keeps its default and the key
     * stays on the flag of the class definition field. A row that carries no such column at all -
     * the table of an installation whose migrations have not run yet - reads the same way.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function castRow(array $data): array
    {
        $data['enabled'] = (bool)$data['enabled'];
        $data[self::COLUMN_LOCALIZED] = isset($data[self::COLUMN_LOCALIZED])
            ? (bool)$data[self::COLUMN_LOCALIZED]
            : null;

        return $data;
    }
}
