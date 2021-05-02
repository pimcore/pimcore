<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\HttpKernel\BundleCollection;

use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class LazyLoadedItem extends AbstractItem
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var BundleInterface
     */
    private $bundle;

    /**
     * @var array
     */
    private static $classImplementsCache = [];

    /**
     * LazyLoadedItem constructor.
     * @param string $className
     * @param int $priority
     * @param array $environments
     * @param string $source
     */
    public function __construct(
        string $className,
        int $priority = 0,
        array $environments = [],
        string $source = self::SOURCE_PROGRAMATICALLY
    ) {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('The class "%s" does not exist', $className));
        }

        $this->className = $className;

        parent::__construct($priority, $environments, $source);
    }

    /**
     * @return string
     */
    public function getBundleIdentifier(): string
    {
        return $this->className;
    }

    /**
     * @return BundleInterface
     */
    public function getBundle(): BundleInterface
    {
        if (null === $this->bundle) {
            $className = $this->className;

            $this->bundle = new $className;
        }

        return $this->bundle;
    }

    /**
     * @return bool
     */
    public function isPimcoreBundle(): bool
    {
        if (null !== $this->bundle) {
            return $this->bundle instanceof PimcoreBundleInterface;
        }

        // do not initialize bundle - check class instead
        return static::implementsInterface($this->className, PimcoreBundleInterface::class);
    }

    /**
     * @param BundleCollection $collection
     */
    public function registerDependencies(BundleCollection $collection)
    {
        if (static::implementsInterface($this->className, DependentBundleInterface::class)) {
            /** @var DependentBundleInterface $className */
            $className = $this->className;
            $className::registerDependentBundles($collection);
        }
    }

    /**
     * @param string $className
     * @param string $interfaceName
     * @return bool
     */
    private static function implementsInterface(string $className, string $interfaceName): bool
    {
        if (!isset(static::$classImplementsCache[$className])) {
            static::$classImplementsCache[$className] = class_implements($className);
        }

        return in_array($interfaceName, static::$classImplementsCache[$className]);
    }
}
