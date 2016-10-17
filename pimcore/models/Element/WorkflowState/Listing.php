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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\WorkflowState;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Element\WorkflowState\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing
{

    /**
     * Contains the results of the list. They are all an instance of WorkflowState
     *
     * @var array
     */
    public $workflowStates = [];

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     * @return boolean
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @param $workflowStates
     * @return $this
     */
    public function setWorkflowStates($workflowStates)
    {
        $this->workflowStates = $workflowStates;

        return $this;
    }

    /**
     * @return array
     */
    public function getWorkflowStates()
    {
        return $this->workflowStates;
    }
}
