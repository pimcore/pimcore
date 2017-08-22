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

namespace Pimcore\Migrations\Migration;

/**
 * Doctrine migrations by default output a warning if no SQL queries were run during a migration. This is
 * perfectly OK for only DB-based migrations, but might happen if a migration changes class definitions which
 * handle their SQL updates implicitely. If a migration implements this interface and doesSqlMigrations() returns
 * false, the warning will be omitted.
 */
interface PimcoreMigrationInterface
{
    /**
     * Determines if a warning should be issues when no SQL queries were executed
     * during a migration. Return false here to omit a warning.
     *
     * @return bool
     */
    public function doesSqlMigrations(): bool;
}
