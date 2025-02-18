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
        $glossarysData = $this->db->fetchFirstColumn('SELECT id FROM glossary' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

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
        $glossarysData = $this->db->fetchAllAssociative('SELECT * FROM glossary' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $glossarysData;
    }

    /**
     *
     * @todo: $amount could not be defined, so this could cause an issue
     */
    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM glossary ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {
            return 0;
        }
    }
}
