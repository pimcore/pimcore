<?php

declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages;

use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\StepExecutionMode;
use Pimcore\Model\Element\ElementDescriptor;

abstract class AbstractExecutionEngineMessage implements GenericExecutionEngineMessageInterface
{
    /**
     * @param ElementDescriptor[] $elements
     */
    public function __construct(
        protected int $jobRunId,
        protected int $currentJobStep,
        /** @deprecated Parameter $element will be removed with Pimcore 12. Use $elements instead. */
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

    /**
     * @deprecated will be removed with Pimcore 12. Use getElements() instead.
     */
    public function getElement(): ?ElementDescriptor
    {
        return $this->element;
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @deprecated will be removed with Pimcore 12. Use constructor instead.
     */
    public function setElements(array $elements): void
    {
        $this->elements = $elements;
    }

    public function getExecutionMode(): StepExecutionMode {
        return StepExecutionMode::FOR_EACH;
    }
}
