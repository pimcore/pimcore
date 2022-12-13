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

namespace Pimcore\Bundle\GlossaryBundle;

use Pimcore\Bundle\NumberSequenceGeneratorBundle\Installer;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class GlossaryBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'pimcore/glossary-bundle';
    }

    public function getCssPaths(): array
    {
        return [];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/glossary/js/glossary.js',
            '/bundles/glossary/js/startup.js',
        ];
    }

    /**
     * @return Installer
     */
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }


    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
