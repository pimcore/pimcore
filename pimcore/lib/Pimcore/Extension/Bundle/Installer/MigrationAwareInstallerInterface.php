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

namespace Pimcore\Extension\Bundle\Installer;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Configuration\Configuration;

interface MigrationAwareInstallerInterface
{
    /**
     * Returns the migration configuration for this bundle
     *
     * @return Configuration
     */
    public function getMigrationConfiguration(): Configuration;

    /**
     * If this returns a version, this migration version will be marked as migrated after an installation. This allows
     * to provide migrations for existing installations while keeping the install routine up to date as new
     * installations won't do any migrations before the one specified here.
     *
     * @return string|null
     */
    public function getMigrationVersion();

    /**
     * Populates the DB schema with bundle specific tables. This is used during the installation for initial
     * creation of database tables.
     *
     * @param Schema $schema
     */
    public function populateSchema(Schema $schema);

    /**
     * Does the opposite of populateSchema on uninstallation.
     *
     * @param Schema $schema
     */
    public function unpopulateSchema(Schema $schema);
}
