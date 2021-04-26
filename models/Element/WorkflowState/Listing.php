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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\WorkflowState;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Element\WorkflowState\Listing\Dao getDao()
 * @method Model\Element\WorkflowState[] load()
 * @method Model\Element\WorkflowState current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param Model\Element\WorkflowState[]|null $workflowStates
     *
     * @return static
     */
    public function setWorkflowStates($workflowStates)
    {
        return $this->setData($workflowStates);
    }

    /**
     * @return Model\Element\WorkflowState[]
     */
    public function getWorkflowStates()
    {
        return $this->getData();
    }
}
