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
