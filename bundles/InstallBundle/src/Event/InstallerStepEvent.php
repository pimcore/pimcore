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

namespace Pimcore\Bundle\InstallBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
class InstallerStepEvent extends Event
{
    private string $type;

    private string $message;

    private int $step;

    private int $totalSteps;

    public function __construct(
        string $type,
        string $message,
        int $step,
        int $totalSteps
    ) {
        $this->type = $type;
        $this->message = $message;
        $this->step = $step;
        $this->totalSteps = $totalSteps;
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
