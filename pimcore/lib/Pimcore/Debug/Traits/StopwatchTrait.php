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

namespace Pimcore\Debug\Traits;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Simple integration into the profiler timeline by adding events to
 * the debug stopwatch. Usage:
 *
 *  - use this trait from a service
 *  - configure the service to use the debug stopwatch if available:
 *
 *         calls:
 *              - [setStopwatch, ['@?debug.stopwatch']]
 */
trait StopwatchTrait
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function setStopwatch(Stopwatch $stopwatch = null)
    {
        $this->stopwatch = $stopwatch;
    }

    private function startStopwatch(string $name, string $category)
    {
        if ($this->stopwatch) {
            $this->stopwatch->start($name, $category);
        }
    }

    private function stopStopwatch(string $name)
    {
        if ($this->stopwatch) {
            $this->stopwatch->stop($name);
        }
    }
}
