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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Targeting\Rule\Listing;

use Pimcore\Model;
use Pimcore\Model\Tool\Targeting\Rule;

/**
 * @property \Pimcore\Model\Tool\Targeting\Rule\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * @return Rule[]
     */
    public function load()
    {
        $ids = $this->db->fetchCol('SELECT id FROM targeting_rules' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $targets = [];
        foreach ($ids as $id) {
            $targets[] = Rule::getById($id);
        }

        $this->model->setTargets($targets);

        return $targets;
    }

    public function getTotalCount()
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM targeting_rules ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
