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

namespace Pimcore\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;

abstract class BundleAwareMigration extends AbstractMigration
{
    abstract protected function getBundleName(): string;

    protected function checkBundleInstalled(): bool
    {
        $bundle = Pimcore::getKernel()->getBundle($this->getBundleName());
        if ($bundle instanceof PimcoreBundleInterface) {
            $installer = $bundle->getInstaller();
            $this->skipIf($installer && !$installer->isInstalled(), 'Bundle not installed.');
        }

        return true;
    }

    public function preUp(Schema $schema): void
    {
        $this->checkBundleInstalled();
        parent::preUp($schema);
    }

    public function preDown(Schema $schema): void
    {
        $this->checkBundleInstalled();
        parent::preDown($schema);
    }
}
