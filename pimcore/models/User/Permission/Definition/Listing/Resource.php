<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User\Permission\Definition\Listing;

use Pimcore\Model;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of definitions for the specicified parameters, returns an array of User\Permission\Definition elements
     *
     * @return array
     */
    public function load() {

        $definitions = array();
        $definitionsData = $this->db->fetchAll("SELECT * FROM users_permission_definitions" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($definitionsData as $definitionData) {
            $definition = new Model\User\Permission\Definition($definitionData);
            $definitions[] = $definition;
        }

        $this->model->setDefinitions($definitions);
        return $definitions;
    }

}
