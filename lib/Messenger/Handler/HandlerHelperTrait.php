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

namespace Pimcore\Messenger\Handler;

trait HandlerHelperTrait
{
    protected function filterUnique(array $jobs, callable $callback): array
    {
        $filteredJobs = [];
        foreach ($jobs as [$message, $ack]) {
            $key = $callback($message);
            if (isset($filteredJobs[$key])) {
                $ack->ack($message);
            } else {
                $filteredJobs[$key] = [$message, $ack];
            }
        }

        return $filteredJobs;
    }
}
