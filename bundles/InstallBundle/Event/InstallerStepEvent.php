<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\InstallBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class InstallerStepEvent extends Event
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $step;

    /**
     * @var int
     */
    private $totalSteps;

    /**
     * @param string $type
     * @param string $message
     * @param int $step
     * @param int $totalSteps
     */
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
