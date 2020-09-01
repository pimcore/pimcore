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
 * @package    User
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\Permission\Definition\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\User\Permission\Definition\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of definitions for the specicified parameters, returns an array of User\Permission\Definition elements
     *
     * @return array
     */
    public function load()
    {
        $definitions = [];
        $definitionsData = $this->db->fetchAll('SELECT * FROM users_permission_definitions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($definitionsData as $definitionData) {
            $definition = new Model\User\Permission\Definition($definitionData);
            $definitions[] = $definition;
        }

        $this->model->setDefinitions($definitions);

        return $definitions;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM users_permission_definitions ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return 0;
        }
    }
}
