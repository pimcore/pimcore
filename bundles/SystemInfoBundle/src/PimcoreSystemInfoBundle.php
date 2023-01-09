<?php

namespace Pimcore\Bundle\SystemInfoBundle;

use Pimcore\Bundle\SystemInfoBundle\DependencyInjection\PimcoreSystemInfoExtension;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class PimcoreSystemInfoBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'pimcore/system-info-bundle';
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcoresysteminfo/css/system-info.css'
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcoresysteminfo/js/startup.js'
        ];
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

}
