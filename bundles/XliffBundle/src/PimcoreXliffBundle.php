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

namespace Pimcore\Bundle\XliffBundle;

use Pimcore\Bundle\XliffBundle\DependencyInjection\Compiler\TranslationServicesPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PimcoreXliffBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorexliff/css/icons.css'
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorexliff/js/startup.js',
            '/bundles/pimcorexliff/js/xliff.js',
        ];
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TranslationServicesPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
