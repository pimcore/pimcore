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

namespace Pimcore\Model\DataObject\Classificationstore\Label\Listing;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\Label;
use Pimcore\Model\DataObject\Classificationstore\Label\Dao as LabelDao;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\Label\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of classification store labels for the specified parameters, returns an array of labels
     *
     * @return Label[]
     */
    public function load(): array
    {
        $labelsData = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . LabelDao::TABLE_NAME_LABELS .
            $this->getCondition() .
            $this->getOrder() .
            $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes()
        );

        $labels = [];

        foreach ($labelsData as $labelData) {
            $label = new Label();
            $label->getDao()->assignRowToModel($labelData);

            $labels[] = $label;
        }

        $this->model->setList($labels);

        return $labels;
    }

    public function getDataArray(): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT * FROM ' . LabelDao::TABLE_NAME_LABELS
            . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes()
        );
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne(
                'SELECT COUNT(*) FROM ' . LabelDao::TABLE_NAME_LABELS . ' ' . $this->getCondition(),
                $this->model->getConditionVariables(),
                $this->model->getConditionVariableTypes()
            );
        } catch (Exception $e) {
            return 0;
        }
    }
}
