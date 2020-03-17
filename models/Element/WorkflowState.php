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

namespace Pimcore\Model\Element;

use Pimcore\Model;

/**
 * @method void delete()
 * @method \Pimcore\Model\Element\WorkflowState\Dao getDao()
 * @method void save()
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
     * @var string
     */
    public $workflow;
    /**
     * @var string
     */
    public $place;

    /**
     * @param int $cid
     * @param string $ctype
     * @param string $workflow
     *
     * @return null|WorkflowState
     */
    public static function getByPrimary(int $cid, string $ctype, string $workflow)
    {
        try {
            $workflowState = new self();
            $workflowState->getDao()->getByPrimary($cid, $ctype, $workflow);

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
     *
     * @return WorkflowState
     */
    public function setCid($cid)
    {
        $this->cid = $cid;

        return $this;
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
     *
     * @return WorkflowState
     */
    public function setCtype($ctype)
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlace(): string
    {
        return $this->place;
    }

    /**
     * @param string $place
     *
     * @return WorkflowState
     */
    public function setPlace(string $place)
    {
        $this->place = $place;

        return $this;
    }

    /**
     * @return string
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param string $workflow
     *
     * @return WorkflowState
     */
    public function setWorkflow(string $workflow)
    {
        $this->workflow = $workflow;

        return $this;
    }
}
