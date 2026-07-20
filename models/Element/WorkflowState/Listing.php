<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Element\WorkflowState;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Element\WorkflowState\Listing\Dao getDao()
 * @method Model\Element\WorkflowState[] load()
 * @method Model\Element\WorkflowState|false current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param Model\Element\WorkflowState[]|null $workflowStates
     *
     * @return $this
     */
    public function setWorkflowStates(?array $workflowStates): static
    {
        return $this->setData($workflowStates);
    }

    /**
     * @return Model\Element\WorkflowState[]
     */
    public function getWorkflowStates(): array
    {
        return $this->getData();
    }
}
