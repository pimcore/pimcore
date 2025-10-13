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

namespace Pimcore\Model\Element\Note\Listing;

use DateTime;
use Exception;
use Pimcore;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Element\Note\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of static routes for the specified parameters, returns an array of Element\Note elements
     *
     * @return Model\Element\Note[]
     */
    public function load(): array
    {
        $notesData = $this->db->fetchAllAssociative(
            'SELECT * FROM notes' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes()
        );

        $notes = [];
        $modelFactory = Pimcore::getContainer()->get('pimcore.model.factory');

        $ids = array_column($notesData, 'id');
        $data = $this->loadDataList($ids);

        foreach ($notesData as $noteData) {
            /** @var Model\Element\Note $note */
            $note = $modelFactory->build(Model\Element\Note::class);
            $note->getDao()->assignVariablesToModel($noteData);
            $note->setData($data[$note->getId()] ?? []);

            $notes[] = $note;
        }

        $this->model->setNotes($notes);

        return $notes;
    }

    public function loadDataList(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $keyValues = $this->db->fetchAllAssociative(
            'SELECT * FROM notes_data WHERE id IN (?)',
            [$ids],
            [\Doctrine\DBAL\ArrayParameterType::INTEGER],
        );

        $list = [];
        foreach ($keyValues as $keyValue) {
            $id = $keyValue['id'];
            $data = $keyValue['data'];
            $type = $keyValue['type'];
            $name = $keyValue['name'];
            if (!array_key_exists($id, $list)) {
                $list[$id] = [];
            }

            if ($type == 'document') {
                if ($data) {
                    $data = Model\Document::getById($data);
                }
            } elseif ($type == 'asset') {
                if ($data) {
                    $data = Model\Asset::getById($data);
                }
            } elseif ($type == 'object') {
                if ($data) {
                    $data = Model\DataObject::getById($data);
                }
            } elseif ($type == 'date') {
                if ($data > 0) {
                    $date = new DateTime();
                    $date->setTimestamp($data);
                    $data = $date;
                }
            } elseif ($type == 'bool') {
                $data = (bool) $data;
            }

            $list[$id][$name] = [
                'data' => $data,
                'type' => $type,
            ];
        }

        return $list;
    }

    /**
     * @return int[]
     */
    public function loadIdList(): array
    {
        $notesIds = $this->db->fetchFirstColumn(
            'SELECT id FROM notes' . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes()
        );

        return array_map('intval', $notesIds);
    }

    public function getTotalCount(): int
    {
        try {
            return (int)$this->db->fetchOne(
                'SELECT COUNT(*) FROM notes ' . $this->getCondition(),
                $this->model->getConditionVariables(),
                $this->model->getConditionVariableTypes()
            );
        } catch (Exception $e) {
            return 0;
        }
    }
}
