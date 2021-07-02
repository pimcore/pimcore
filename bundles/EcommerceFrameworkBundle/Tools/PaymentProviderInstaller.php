<?php

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tools;

use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Objectbrick;

class PaymentProviderInstaller extends AbstractInstaller
{
    /**
     * @var string // json source path
     */
    protected $bricksPath;

    /**
     * @var array //$brickKey => $brickImportJsonPath
     */
    protected $bricksToInstall = [];

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled()
    {
        return !$this->isInstalled();
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled()
    {
        return $this->isInstalled();
    }

    public function install()
    {
        $this->installBricks();

        return true;
    }

    public function uninstall()
    {
        $this->unInstallBricks();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled()
    {
        $installed = false;

        try {
            // check if payment brick exists
            foreach ($this->bricksToInstall as $brickKey => $brickFile) {
                $installed = Objectbrick\Definition::getByKey($brickKey);
            }
        } catch (\Exception $e) {
            // nothing to do
        }

        return (bool) $installed;
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return true;
    }

    protected function installBricks()
    {
        foreach ($this->bricksToInstall as $brickKey => $brickFile) {
            self::installBrick($brickKey, $this->bricksPath . $brickFile);
        }
    }

    protected function unInstallBricks()
    {
        foreach ($this->bricksToInstall as $brickKey => $brickFile) {
            $brick = Objectbrick\Definition::getByKey($brickKey);
            if ($brick instanceof Objectbrick\Definition) {
                $brick->delete();
            }
        }
    }

    protected static function installBrick($brickKey, $filepath)
    {
        try {
            $brick = Objectbrick\Definition::getByKey($brickKey);
        } catch (\Exception $e) {
            $brick = null;
        }

        if (!$brick) {
            $brick = new Objectbrick\Definition;
            $brick->setKey($brickKey);

            $json = file_get_contents($filepath);

            $success = Service::importObjectBrickFromJson($brick, $json);

            if ($success) {
                $onlineOrderClass = ClassDefinition::getByName('OnlineShopOrder');
                /** @var ClassDefinition\Data\Objectbricks $paymentProviderBrickField */
                $paymentProviderBrickField = $onlineOrderClass->getFieldDefinition('paymentProvider');
                $allowedTypes = $paymentProviderBrickField->getAllowedTypes();
                $paymentProviderBrickField->setAllowedTypes([$brickKey, ...$allowedTypes]);
            }
        }
    }
}
