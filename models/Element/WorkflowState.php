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

namespace Pimcore\Model\Element;

use Pimcore\Model;

/**
 * @method void delete()
 * @method \Pimcore\Model\Element\WorkflowState\Dao getDao()
 * @method void save()
 */
class WorkflowState extends Model\AbstractModel
{
    protected int $cid;

    protected string $ctype;

    protected string $workflow;

    protected string $place;

    public static function getByPrimary(int $cid, string $ctype, string $workflow): ?WorkflowState
    {
        try {
            $workflowState = new self();
            $workflowState->getDao()->getByPrimary($cid, $ctype, $workflow);

            return $workflowState;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    /**
     * @return $this
     */
    public function setCid(int $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    /**
     * @return $this
     */
    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    public function getPlace(): string
    {
        return $this->place;
    }

    /**
     * @return $this
     */
    public function setPlace(string $place): static
    {
        $this->place = $place;

        return $this;
    }

    public function getWorkflow(): string
    {
        return $this->workflow;
    }

    /**
     * @return $this
     */
    public function setWorkflow(string $workflow): static
    {
        $this->workflow = $workflow;

        return $this;
    }
}
