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
