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
 * @category   Pimcore
 * @package    Glossary
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Glossary\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Glossary\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Staticroute elements
     *
     * @return array
     */
    public function load()
    {
        $glossarysData = $this->db->fetchCol("SELECT id FROM glossary" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $glossary = [];
        foreach ($glossarysData as $glossaryData) {
            $glossary[] = Model\Glossary::getById($glossaryData);
        }

        $this->model->setGlossary($glossary);

        return $glossary;
    }

    /**
     * @return array
     */
    public function getDataArray()
    {
        $glossarysData = $this->db->fetchAll("SELECT * FROM glossary" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        return $glossarysData;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $amount = 0;

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM glossary " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
