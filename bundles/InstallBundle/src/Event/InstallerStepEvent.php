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

namespace Pimcore\Bundle\InstallBundle\Event;

use Pimcore\Bundle\InstallBundle\Profile\InstallStep;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
final class InstallerStepEvent extends Event
{
    public function __construct(
        private readonly InstallStep $step,
        private readonly string $message,
        private readonly int $stepNumber,
        private readonly int $totalSteps,
    ) {
    }

    public function getStep(): InstallStep
    {
        return $this->step;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStepNumber(): int
    {
        return $this->stepNumber;
    }

    public function getTotalSteps(): int
    {
        return $this->totalSteps;
    }
}
