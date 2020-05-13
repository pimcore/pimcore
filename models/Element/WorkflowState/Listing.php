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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
     * @var Model\Element\WorkflowState[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $workflowStates = null;

    public function __construct()
    {
        $this->workflowStates = & $this->data;
    }

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
