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

namespace Pimcore\Bundle\SimpleBackendSearchBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

/**
 * @deprecated version 12.3
 */
class PimcoreSimpleBackendSearchBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public function __construct()
    {
        trigger_deprecation(
            'pimcore/simple-backend-search-bundle',
            '12.3',
            'The SimpleBackendSearchBundle is deprecated and will be discontinued with Pimcore Studio.'
        );
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoresimplebackendsearch/js/pimcore/startup.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/element/service.js',

            '/bundles/pimcoresimplebackendsearch/js/pimcore/element/selector/abstract.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/element/selector/asset.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/element/selector/document.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/element/selector/object.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/element/selector/selector.js',

            '/bundles/pimcoresimplebackendsearch/js/pimcore/layout/toolbar.js',
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
}
