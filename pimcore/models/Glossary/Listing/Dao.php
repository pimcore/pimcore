<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Glossary
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Glossary\Listing;

use Pimcore\Model;

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

        $glossary = array();
        foreach ($glossarysData as $glossaryData) {
            $glossary[] = Model\Glossary::getById($glossaryData);
        }

        $this->model->setGlossary($glossary);
        return $glossary;
    }

    public function getDataArray()
    {
        $glossarysData = $this->db->fetchAll("SELECT * FROM glossary" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $glossarysData;
    }

    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM glossary " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
