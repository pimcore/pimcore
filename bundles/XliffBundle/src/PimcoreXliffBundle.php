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

namespace Pimcore\Bundle\XliffBundle;

use Pimcore\Bundle\XliffBundle\DependencyInjection\Compiler\TranslationServicesPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @deprecated version 12.3
 */
class PimcoreXliffBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public function __construct()
    {
        trigger_deprecation(
            'pimcore/xliff-bundle',
            '12.3',
            'The XliffBundle is deprecated and will be discontinued with Pimcore Studio.'
        );
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorexliff/js/startup.js',
            '/bundles/pimcorexliff/js/settings.js',
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TranslationServicesPass());
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
