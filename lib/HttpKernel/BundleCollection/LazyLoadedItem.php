<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\HttpKernel\BundleCollection;

use InvalidArgumentException;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class LazyLoadedItem extends AbstractItem
{
    private string $className;

    private ?BundleInterface $bundle = null;

    private static array $classImplementsCache = [];

    /**
     * LazyLoadedItem constructor.
     *
     */
    public function __construct(
        string $className,
        int $priority = 0,
        array $environments = [],
        string $source = self::SOURCE_PROGRAMATICALLY
    ) {
        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('The class "%s" does not exist', $className));
        }

        $this->className = $className;

        parent::__construct($priority, $environments, $source);
    }

    public function getBundleIdentifier(): string
    {
        return $this->className;
    }

    public function getBundle(): BundleInterface
    {
        if (null === $this->bundle) {
            $className = $this->className;

            $this->bundle = new $className;
        }

        return $this->bundle;
    }

    public function isPimcoreBundle(): bool
    {
        if (null !== $this->bundle) {
            return $this->bundle instanceof PimcoreBundleInterface;
        }

        // do not initialize bundle - check class instead
        return self::implementsInterface($this->className, PimcoreBundleInterface::class);
    }

    public function registerDependencies(BundleCollection $collection): void
    {
        if (self::implementsInterface($this->className, DependentBundleInterface::class)) {
            /** @var class-string<DependentBundleInterface> $className */
            $className = $this->className;
            $className::registerDependentBundles($collection);
        }
    }

    private static function implementsInterface(string $className, string $interfaceName): bool
    {
        if (!isset(self::$classImplementsCache[$className])) {
            self::$classImplementsCache[$className] = class_implements($className);
        }

        return in_array($interfaceName, self::$classImplementsCache[$className]);
    }
}
