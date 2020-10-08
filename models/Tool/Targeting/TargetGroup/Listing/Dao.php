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

namespace Pimcore\Model\Tool\Targeting\TargetGroup\Listing;

use Pimcore\Model;
use Pimcore\Model\Tool\Targeting\TargetGroup;

/**
 * @property TargetGroup\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * @return TargetGroup[]
     */
    public function load()
    {
        $ids = $this->db->fetchCol('SELECT id FROM targeting_target_groups' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $targetGroups = [];
        foreach ($ids as $id) {
            $targetGroups[] = TargetGroup::getById($id);
        }

        $this->model->setTargetGroups($targetGroups);

        return $targetGroups;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM targeting_target_groups ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
