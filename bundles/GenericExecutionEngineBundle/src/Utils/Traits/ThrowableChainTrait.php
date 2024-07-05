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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Traits;

use Throwable;

/**
 * @internal
 */
trait ThrowableChainTrait
{
    private function getThrowableChain(Throwable $throwable): array
    {
        $chain = [];

        do {
            $chain[] = $throwable;
        } while ($throwable = $throwable->getPrevious());

        return $chain;
    }

    private function getFirstThrowable(Throwable $throwable): Throwable
    {
        $throwables = $this->getThrowableChain($throwable);

        return end($throwables);
    }
}
