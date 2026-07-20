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

namespace Pimcore\Bundle\GlossaryBundle\Model\Glossary\Listing;

use Exception;
use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Bundle\GlossaryBundle\Model\Glossary\Listing;
use Pimcore\Model;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Staticroute elements
     *
     * @return Glossary[]
     */
    public function load(): array
    {
        $glossarysData = $this->db->fetchFirstColumn('SELECT id FROM glossary' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $glossary = [];
        foreach ($glossarysData as $glossaryData) {
            $glossary[] = Glossary::getById($glossaryData);
        }

        $this->model->setGlossary($glossary);

        return $glossary;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function getDataArray(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM glossary' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    /**
     *
     * @todo: $amount could not be defined, so this could cause an issue
     */
    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM glossary ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
