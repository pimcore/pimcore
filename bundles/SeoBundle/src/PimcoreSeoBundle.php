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

namespace Pimcore\Bundle\SeoBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Presta\SitemapBundle\PrestaSitemapBundle;

/**
 * @deprecated version 12.3
 */
class PimcoreSeoBundle extends AbstractPimcoreBundle implements DependentBundleInterface, PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public function __construct()
    {
        trigger_deprecation(
            'pimcore/seo-bundle',
            '12.3',
            'The SeoBundle is deprecated and will be discontinued with Pimcore Studio.'
        );
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcoreseo/css/icons.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoreseo/js/startup.js',
            '/bundles/pimcoreseo/js/httpErrorLog.js',
            '/bundles/pimcoreseo/js/robotstxt.js',
            '/bundles/pimcoreseo/js/seopanel.js',
            '/bundles/pimcoreseo/js/redirects.js',
        ];
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(PrestaSitemapBundle::class);
    }
}
