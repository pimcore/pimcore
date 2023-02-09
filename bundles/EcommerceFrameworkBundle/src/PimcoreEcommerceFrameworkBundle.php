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

namespace Pimcore\Bundle\EcommerceFrameworkBundle;

use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\Compiler\RegisterConfiguredServicesPass;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Installer;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Version;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PimcoreEcommerceFrameworkBundle extends AbstractPimcoreBundle implements DependentBundleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return sprintf('%s build %s', Version::getVersion(), Version::getRevision());
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterConfiguredServicesPass());
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcoreecommerceframework/css/backend.css',
            '/bundles/pimcoreecommerceframework/css/pricing.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/data/indexFieldSelectionField.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/tags/indexFieldSelectionField.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/data/indexFieldSelectionCombo.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/tags/indexFieldSelectionCombo.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/data/indexFieldSelection.js',
            '/bundles/pimcoreecommerceframework/js/indexfieldselectionfield/tags/indexFieldSelection.js',
            '/bundles/pimcoreecommerceframework/js/startup.js',
            '/bundles/pimcoreecommerceframework/js/pricing/config/panel.js',
            '/bundles/pimcoreecommerceframework/js/pricing/config/item.js',
            '/bundles/pimcoreecommerceframework/js/pricing/config/objects.js',
            '/bundles/pimcoreecommerceframework/js/voucherservice/VoucherSeriesTab.js',
            '/bundles/pimcoreecommerceframework/js/order/OrderTab.js',
            '/admin/ecommerceframework/config/js-config',
        ];
    }

    public function boot(): void
    {
        $container = $this->container;
        // set default decimal scale from config
        Decimal::setDefaultScale($container->getParameter('pimcore_ecommerce.decimal_scale'));
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        if (\Pimcore\Version::getMajorVersion() >= 11) {
            $collection->addBundle(\Pimcore\Bundle\ApplicationLoggerBundle\PimcoreApplicationLoggerBundle::class);
        }
    }
}
