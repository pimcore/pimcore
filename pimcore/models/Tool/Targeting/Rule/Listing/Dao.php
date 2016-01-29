<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Tool\Targeting\Rule\Listing;

use Pimcore\Model;

class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * Loads a list of document-types for the specicifies parameters, returns an array of Document\DocType elements
     *
     * @return array
     */
    public function load()
    {
        $targetsData = $this->db->fetchCol("SELECT id FROM targeting_rules" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $targets = array();
        foreach ($targetsData as $targetData) {
            $targets[] = Model\Tool\Targeting\Rule::getById($targetData);
        }

        $this->model->setTargets($targets);
        return $targets;
    }
}
