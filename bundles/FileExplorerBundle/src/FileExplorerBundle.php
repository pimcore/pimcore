<?php

namespace Pimcore\Bundle\FileExplorerBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\Bundle\FileExplorerBundle\DependencyInjection\FileExplorerExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class FileExplorerBundle extends AbstractPimcoreBundle
{

    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'pimcore/file-explorer-bundle';
    }

    public function getCssPaths(): array
    {
        return [];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/fileexplorer/js/startup.js',
            '/bundles/fileexplorer/js/explorer.js',
            '/bundles/fileexplorer/js/file.js',
        ];
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new FileExplorerExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

}
