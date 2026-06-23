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

namespace Pimcore\Extension\Bundle;

use Pimcore;
use Symfony\Component\HttpKernel\Bundle\Bundle;

abstract class AbstractPimcoreBundle extends Bundle implements PimcoreBundleInterface
{
    public function getNiceName(): string
    {
        return $this->getName();
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getVersion(): string
    {
        return '';
    }

    public function getInstaller(): ?Installer\InstallerInterface
    {
        return null;
    }

    public static function isInstalled(): bool
    {
        $bundleManager = Pimcore::getContainer()?->get(PimcoreBundleManager::class);
        if (!$bundleManager) {
            return false;
        }
        $bundle = $bundleManager->getActiveBundle(static::class, false);

        return $bundleManager->isInstalled($bundle);
    }
}
