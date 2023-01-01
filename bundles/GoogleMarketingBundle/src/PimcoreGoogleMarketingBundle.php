<?php

namespace Pimcore\Bundle\GoogleMarketingBundle;

use Pimcore\Bundle\GoogleMarketingBundle\DependencyInjection\PimcoreGoogleMarketingExtension;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
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
        ];
    }



    public function getContainerExtension(): ?ExtensionInterface
    {
        return new PimcoreGoogleMarketingExtension();
    }

}
