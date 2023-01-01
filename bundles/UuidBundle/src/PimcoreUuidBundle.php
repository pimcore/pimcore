<?php

namespace Pimcore\Bundle\UuidBundle;

use Pimcore\Bundle\UuidBundle\DependencyInjection\PimcoreUuidExtension;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class PimcoreUuidBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getComposerPackageName(): string
    {
        return 'pimcore/uuid-bundle';
    }
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
    public function getCssPaths(): array
    {
        return [];
    }
    public function getJsPaths(): array
    {
        return [];
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new PimcoreUuidExtension();
    }

}
