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

namespace Pimcore\Bundle\SimpleBackendSearchBundle;

use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class PimcoreSimpleBackendSearchBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcoresimplebackendsearch/css/icons.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoresimplebackendsearch/js/pimcore/events.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/startup.js',

            '/bundles/pimcoresimplebackendsearch/js/pimcore/layout/toolbar.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/layout/quickSearch.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/layout/searchModal.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/layout/searchButton.js',

            '/bundles/pimcoresimplebackendsearch/js/pimcore/selector/abstract.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/selector/asset.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/selector/document.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/selector/object.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/selector/selector.js',

            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/abstractRelations.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/advancedManyToManyObjectRelation.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/advancedManyToManyRelation.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/manyToManyObjectRelation.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/manyToManyRelation.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/manyToOneRelation.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/hotspotimage.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/image.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/imagegallery.js',
            '/bundles/pimcoresimplebackendsearch/js/pimcore/object/tags/link.js',
        ];
    }

    public function getInstaller(): ?InstallerInterface
    {
        return null;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
