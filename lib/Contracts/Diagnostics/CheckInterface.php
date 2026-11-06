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

namespace Pimcore\Contracts\Diagnostics;

/**
 * A read-only diagnostic any bundle may provide. Core registers and runs nothing: a consumer of the checks collects
 * the services tagged with {@see self::TAG}; without such a consumer a check is never run.
 */
interface CheckInterface
{
    public const string TAG = 'pimcore.diagnostics.check';

    /**
     * Stable snake_case identifier, unique across all checks. Prefix it with a short bundle name,
     * e.g. generic_data_index.index_queue_backlog.
     */
    public function getId(): string;

    /**
     * Must not change anything on the instance and must not put secrets or personal data into the result.
     */
    public function run(): CheckResult;
}
