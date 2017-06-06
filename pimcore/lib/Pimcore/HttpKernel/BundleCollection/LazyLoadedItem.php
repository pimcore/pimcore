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
     * @param string $className
     * @param int $priority
     * @param array $environments
     */
    public function __construct(string $className, int $priority = 0, array $environments = [])
    {
        $this->className = $className;

        parent::__construct($priority, $environments);
    }

    public function getBundleIdentifier(): string
    {
        return $this->className;
    }

    public function getBundle(): BundleInterface
    {
        if (null === $this->bundle) {
            $className = $this->className;
            if (!class_exists($className)) {
                throw new \InvalidArgumentException(sprintf('The class "%s" does not exist', $className));
            }

            $this->bundle = new $className;
        }

        return $this->bundle;
    }
}
