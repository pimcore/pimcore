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

abstract class AbstractItem implements ItemInterface
{
    /**
     * @var int
     */
    private $priority;

    /**
     * @var array
     */
    private $environments = [];

    /**
     * @var string
     */
    private $source;

    public function __construct(int $priority = 0, array $environments = [], string $source = self::SOURCE_PROGRAMATICALLY)
    {
        $this->priority = $priority;
        $this->environments = $environments;
        $this->source = $source;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getEnvironments(): array
    {
        return $this->environments;
    }

    public function matchesEnvironment(string $environment): bool
    {
        if (empty($this->environments)) {
            return true;
        }

        return in_array($environment, $this->environments, true);
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
