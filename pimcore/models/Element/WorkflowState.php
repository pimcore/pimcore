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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Element\WorkflowState\Dao getDao()
 */
class WorkflowState extends Model\AbstractModel
{

    /**
     * @var int
     */
    public $cid;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var int
     */
    public $workflowId;
    /**
     * @var string
     */
    public $state;

    /**
     * @var string
     */
    public $status;


    /**
     * @param $cid
     * @param $ctype
     * @param $workflowId
     * @return null|WorkflowState
     */
    public static function getByPrimary($cid, $ctype, $workflowId)
    {
        try {
            $workflowState = new self();
            $workflowState->getDao()->getByPrimary($cid, $ctype, $workflowId);

            return $workflowState;
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @param int $cid
     */
    public function setCid($cid)
    {
        $this->cid = $cid;
    }

    /**
     * @return string
     */
    public function getCtype()
    {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     */
    public function setCtype($ctype)
    {
        $this->ctype = $ctype;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getWorkflowId()
    {
        return $this->workflowId;
    }

    /**
     * @param int $workflowId
     */
    public function setWorkflowId($workflowId)
    {
        $this->workflowId = $workflowId;
    }
}
