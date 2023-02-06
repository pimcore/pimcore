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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule;
use Pimcore\Db\Helper;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property Rule $model
 */
class Dao extends AbstractDao
{
    const TABLE_NAME = 'ecommerceframework_pricing_rule';

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected array $validColumns = [];

    protected array $fieldsToSave = ['name', 'label', 'description', 'behavior', 'active', 'prio', 'condition', 'actions'];

    protected array $localizedFields = ['label', 'description'];

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init(): void
    {
        $this->validColumns = $this->getValidTableColumns(self::TABLE_NAME);
    }

    /**
     * @param int $id
     *
     * @throws NotFoundException
     */
    public function getById(int $id): void
    {
        $classRaw = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id=' . $this->db->quote($id));
        if (empty($classRaw)) {
            throw new NotFoundException('pricing rule ' . $id . ' not found.');
        }
        $this->assignVariablesToModel($classRaw);
    }

    /**
     * Create a new record for the object in database
     */
    public function create(): void
    {
        $this->db->insert(self::TABLE_NAME, []);
        $this->model->setId((int) $this->db->lastInsertId());
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $this->update();
    }

    public function update(): void
    {
        $data = [];

        foreach ($this->fieldsToSave as $field) {
            if (in_array($field, $this->validColumns)) {
                $getter = 'get' . ucfirst($field);

                if (in_array($field, $this->localizedFields)) {
                    // handle localized Fields
                    $localizedValues = [];
                    foreach (\Pimcore\Tool::getValidLanguages() as $lang) {
                        $localizedValues[$lang] = $value = $this->model->$getter($lang);
                    }
                    $value = $localizedValues;
                } else {
                    // normal case
                    $value = $this->model->$getter();
                }

                if (is_array($value) || is_object($value)) {
                    $value = serialize($value);
                } elseif (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$field] = $value;
            }
        }

        $this->db->update(self::TABLE_NAME, Helper::quoteDataIdentifiers($this->db, $data), ['id' => $this->model->getId()]);
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete(): void
    {
        $this->db->delete(self::TABLE_NAME, ['id' => $this->model->getId()]);
    }

    public function setFieldsToSave(array $fields): void
    {
        $this->fieldsToSave = $fields;
    }
}
