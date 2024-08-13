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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Model;

use InvalidArgumentException;
use Pimcore\Model\Element\ElementDescriptor;

final class Job
{
    /**
     * @param JobStep[] $steps
     * @param ElementDescriptor[] $selectedElements
     */
    public function __construct(
        private readonly string $name,
        private readonly array $steps,
        private array $selectedElements = [],
        private readonly array $environmentData = []
    ) {
        if (empty($this->steps)) {
            throw new InvalidArgumentException('Job must have at least one step');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return JobStep[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @return ElementDescriptor[] $selectedElements
     */
    public function getSelectedElements(): array
    {
        return $this->selectedElements;
    }

    public function getEnvironmentData(): array
    {
        return $this->environmentData;
    }

    /**
     * @param ElementDescriptor[] $selectedElements
     */
    public function setSelectedElements(array $selectedElements): void
    {
        $this->selectedElements = $selectedElements;
    }
}
