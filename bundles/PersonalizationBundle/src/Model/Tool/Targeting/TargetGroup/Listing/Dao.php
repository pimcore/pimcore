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

namespace Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup\Listing;

use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;
use Pimcore\Model;

/**
 * @internal
 *
 * @property TargetGroup\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * @return TargetGroup[]
     */
    public function load(): array
    {
        $ids = $this->db->fetchFirstColumn('SELECT id FROM targeting_target_groups' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $targetGroups = [];
        foreach ($ids as $id) {
            $targetGroups[] = TargetGroup::getById($id);
        }

        $this->model->setTargetGroups($targetGroups);

        return $targetGroups;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM targeting_target_groups ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
