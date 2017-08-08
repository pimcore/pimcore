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

namespace Pimcore\Extension\Bundle\Migrations\Migration;

/**
 * Used in migrations handling something else than DB migrations (e.g. changing class definitions). As a normal
 * doctrine migration does not know about the dry-run switch (SQL is simply not executed), we need to pass the
 * dry-run state to the migration itself.
 */
interface DryRunMigrationInterface
{
    /**
     * Dry-run will be set by the version if the --dry-run switch was passed
     * on the CLI
     *
     * @param bool $dryRun
     *
     * @return mixed
     */
    public function setDryRun(bool $dryRun);

    /**
     * Migrations implementing this interface can check the dry-run state
     * on their own and omit changing data (e.g. changing a class definition)
     * if dry-run is set
     *
     * @return bool
     */
    public function isDryRun(): bool;
}
