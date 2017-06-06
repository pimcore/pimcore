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

class Item extends AbstractItem
{
    /**
     * @var BundleInterface
     */
    private $bundle;

    /**
     * @param BundleInterface $bundle
     * @param int $priority
     * @param array $environments
     */
    public function __construct(BundleInterface $bundle, int $priority = 0, array $environments = [])
    {
        $this->bundle = $bundle;

        parent::__construct($priority, $environments);
    }

    public function getBundleIdentifier(): string
    {
        return get_class($this->bundle);
    }

    public function getBundle(): BundleInterface
    {
        return $this->bundle;
    }
}
