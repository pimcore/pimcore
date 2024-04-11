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

namespace Pimcore\Bundle\JobExecutionEngineBundle\Model;

use Pimcore\Model\Element\ElementDescriptor;

class Job
{
    /**
     * @param string $name
     * @param JobStep[] $steps
     * @param null|ElementDescriptor $subject
     * @param null|ElementDescriptor[] $selectedElements
     * @param array $environmentData
     */
    public function __construct(
        protected string $name,
        protected array $steps = [],
        protected ?ElementDescriptor $subject = null,
        protected ?array $selectedElements = null,
        protected array $environmentData = []
    ) {
    }

    /**
     * @return string
     */
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
     * @return ElementDescriptor|null
     */
    public function getSubject(): ?ElementDescriptor
    {
        return $this->subject;
    }

    /**
     * @return array|null
     */
    public function getSelectedElements(): ?array
    {
        return $this->selectedElements;
    }

    /**
     * @return array
     */
    public function getEnvironmentData(): array
    {
        return $this->environmentData;
    }
}
