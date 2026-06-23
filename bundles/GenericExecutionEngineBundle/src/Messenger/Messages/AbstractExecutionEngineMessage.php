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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages;

use Pimcore\Model\Element\ElementDescriptor;

abstract class AbstractExecutionEngineMessage implements GenericExecutionEngineMessageInterface
{
    /**
     * @param ElementDescriptor[] $elements
     */
    public function __construct(
        protected int $jobRunId,
        protected int $currentJobStep,
        protected ?ElementDescriptor $element = null,
        protected array $elements = []
    ) {
    }

    public function getJobRunId(): int
    {
        return $this->jobRunId;
    }

    public function getCurrentJobStep(): int
    {
        return $this->currentJobStep;
    }

    public function getElement(): ?ElementDescriptor
    {
        return $this->element;
    }
}
