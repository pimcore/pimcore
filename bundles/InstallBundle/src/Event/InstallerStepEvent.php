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

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
class InstallerStepEvent extends Event
{
    public function __construct(
        private readonly string $type,
        private readonly string $message,
        private readonly int $step,
        private readonly int $totalSteps,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function getTotalSteps(): int
    {
        return $this->totalSteps;
    }
}
