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

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\MigrationInstallerInterface;
use Pimcore\Migrations\InstallVersion;

/**
 * This migration is executed when a bundle is about to be installed/uninstalled and is built in
 * conjunction with InstallConfiguration and InstallVersion.
 */
class InstallMigration extends AbstractPimcoreMigration
{
    /**
     * @var MigrationInstallerInterface
     */
    protected $installer;

    public function __construct(MigrationInstallerInterface $installer, InstallVersion $version)
    {
        parent::__construct($version);

        $this->installer = $installer;
    }

    public function up(Schema $schema)
    {
        $this->installer->migrateInstall($schema, $this->version);
    }

    public function down(Schema $schema)
    {
        $this->installer->migrateUninstall($schema, $this->version);
    }
}
