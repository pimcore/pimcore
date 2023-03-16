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

namespace Pimcore\Bundle\PersonalizationBundle;

use Pimcore\Bundle\PersonalizationBundle\DependencyInjection\Compiler\DebugStopwatchPass;
use Pimcore\Bundle\PersonalizationBundle\DependencyInjection\Compiler\TargetingOverrideHandlersPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PimcorePersonalizationBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    // @TODO Enable when bundle is moved to own repo

    /*public function getComposerPackageName(): string
    {
       return 'pimcore/personalization-bundle';
    }*/

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorepersonalization/css/icons.css',
            '/bundles/pimcorepersonalization/css/targeting.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorepersonalization/js/startup.js',
            '/bundles/pimcorepersonalization/js/settings/condition/abstract.js',
            '/bundles/pimcorepersonalization/js/settings/conditions.js',
            '/bundles/pimcorepersonalization/js/settings/action/abstract.js',
            '/bundles/pimcorepersonalization/js/settings/actions.js',
            '/bundles/pimcorepersonalization/js/settings/rules/panel.js',
            '/bundles/pimcorepersonalization/js/settings/rules/item.js',
            '/bundles/pimcorepersonalization/js/settings/targetGroups/panel.js',
            '/bundles/pimcorepersonalization/js/settings/targetGroups/item.js',
            '/bundles/pimcorepersonalization/js/settings/targetingtoolbar.js',
            '/bundles/pimcorepersonalization/js/targeting.js',
            '/bundles/pimcorepersonalization/js/document/areatoolbar.js',
            '/bundles/pimcorepersonalization/js/object/classes/data/targetGroup.js',
            '/bundles/pimcorepersonalization/js/object/classes/data/targetGroupMultiselect.js',
            '/bundles/pimcorepersonalization/js/object/tags/targetGroup.js',
            '/bundles/pimcorepersonalization/js/object/tags/targetGroupMultiselect.js',
            '/bundles/pimcorepersonalization/js/ecommerce/pricing/conditions/targetGroup.js',
        ];
    }

    /**
     * @return Installer
     */
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TargetingOverrideHandlersPass());
        $container->addCompilerPass(new DebugStopwatchPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
