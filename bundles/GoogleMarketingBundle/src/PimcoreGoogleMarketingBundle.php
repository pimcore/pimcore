<?php

namespace Pimcore\Bundle\GoogleMarketingBundle;

use Pimcore\Bundle\GoogleMarketingBundle\DependencyInjection\PimcoreGoogleMarketingExtension;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class PimcoreGoogleMarketingBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'pimcore/google-marketing-bundle';
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcoregooglemarketing/css/googlemarketing.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoregooglemarketing/js/startup.js',
            '/bundles/pimcoregooglemarketing/js/reports/analytics/elementexplorer.js',
            '/bundles/pimcoregooglemarketing/js/reports/analytics/elementoverview.js',
            '/bundles/pimcoregooglemarketing/js/reports/analytics/settings.js',
            '/bundles/pimcoregooglemarketing/js/reports/tagmanager/settings.js',
            '/bundles/pimcoregooglemarketing/js/reports/webmastertools/settings.js',
        ];
    }

    public function getInstaller(): ?Installer\InstallerInterface
    {
        /** @var \Pimcore\Bundle\GoogleMarketingBundle\Installer $installer */
        $installer = $this->container->get(\Pimcore\Bundle\GoogleMarketingBundle\Installer::class);
        return $installer;
    }

}
