<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\HttpKernel\BundleCollection;

use Pimcore\Extension\Bundle\PimcoreBundleInterface;
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

    public function __construct(
        string $className,
        int $priority = 0,
        array $environments = [],
        string $source = self::SOURCE_PROGRAMATICALLY
    )
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('The class "%s" does not exist', $className));
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
        return in_array(
            PimcoreBundleInterface::class,
            class_implements($this->className)
        );
    }
}
