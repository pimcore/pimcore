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
 * @package    Tool
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Targeting\Rule\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Tool\Targeting\Rule\Listing $model
 */
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

        $targets = [];
        foreach ($targetsData as $targetData) {
            $targets[] = Model\Tool\Targeting\Rule::getById($targetData);
        }

        $this->model->setTargets($targets);

        return $targets;
    }
}
