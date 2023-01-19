<?php

namespace Pimcore\Bundle\FileExplorerBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\Bundle\FileExplorerBundle\DependencyInjection\PimcoreFileExplorerExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use function dirname;

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

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @return Installer
     */
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

}
