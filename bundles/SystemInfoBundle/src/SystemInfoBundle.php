<?php

namespace Pimcore\Bundle\SystemInfoBundle;

use Pimcore\Bundle\SystemInfoBundle\DependencyInjection\SystemInfoExtension;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class SystemInfoBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'pimcore/system-info-bundle';
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/systeminfo/css/system-info.css'
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/systeminfo/js/startup.js'
        ];
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SystemInfoExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

}
