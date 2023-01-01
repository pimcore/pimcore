<?php

namespace Pimcore\Bundle\FileExplorerBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\Bundle\FileExplorerBundle\DependencyInjection\PimcoreFileExplorerExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class PimcoreFileExplorerBundle extends AbstractPimcoreBundle
{

    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'pimcore/file-explorer-bundle';
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorefileexplorer/css/file-explorer.css'
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorefileexplorer/js/startup.js',
            '/bundles/pimcorefileexplorer/js/explorer.js',
            '/bundles/pimcorefileexplorer/js/file.js',
        ];
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new PimcoreFileExplorerExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

}
