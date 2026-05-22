<?php
declare(strict_types=1);

namespace Pimcore\Controller\Config\Bundle;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class BundleProvider
{
    private ?array $bundles = null;

    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * Returns all eligible bundles (bundles that are not excluded by {@see self::isValidNamespace()}).
     *
     * @return array<string, BundleInterface>
     */
    public function getBundles(): array
    {
        if (null !== $this->bundles) {
            return $this->bundles;
        }

        $this->bundles = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            if ($this->isValidNamespace(get_class($bundle))) {
                $this->bundles[$bundle->getName()] = $bundle;
            }
        }

        return $this->bundles;
    }

    /**
     * Checks if a bundle namespace is not excluded.
     *
     * Note: all core bundles should be excluded here.
     */
    public function isValidNamespace(string $namespace): bool
    {
        if (preg_match('/^(Symfony|Doctrine|Pimcore|Sensio)/', $namespace)) {
            return false;
        }

        return true;
    }
}
