<?php

namespace Pimcore\Bundle\UuidBundle;

use Pimcore\Bundle\UuidBundle\DependencyInjection\PimcoreUuidExtension;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Bundle\UuidBundle\Installer;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class PimcoreUuidBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new PimcoreUuidExtension();
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }
}
