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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Db;
use Pimcore\Maintenance\TaskInterface;

/**
 * @internal
 */
class VersionsCleanupStackTraceDbTask implements TaskInterface
{
    public function execute(): void
    {
        Db::get()->executeStatement(
            'UPDATE versions SET stackTrace = NULL WHERE date < ? AND stackTrace IS NOT NULL',
            [time() - 86400 * 7]
        );
    }
}
