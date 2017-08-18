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

namespace Pimcore\Migrations;

use Pimcore\Extension\Bundle\Installer\MigrationInstallerInterface;
use Pimcore\Migrations\Configuration\Configuration;
use Pimcore\Migrations\Migration\InstallMigration;

class InstallVersion extends Version
{
    const INSTALL_VERSION = '00000001';

    /**
     * @var MigrationInstallerInterface
     */
    private $installer;

    public function __construct(
        MigrationInstallerInterface $installer,
        Configuration $configuration
    )
    {
        $this->installer = $installer;

        parent::__construct($configuration, self::INSTALL_VERSION, get_class($installer));
    }

    public function getInstaller(): MigrationInstallerInterface
    {
        return $this->installer;
    }

    protected function createMigration()
    {
        return new InstallMigration($this->installer, $this);
    }
}
