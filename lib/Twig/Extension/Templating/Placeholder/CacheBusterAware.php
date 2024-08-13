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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Twig\Extension\Templating\Placeholder;

/**
 * adds cache buster functionality to placeholder extension
 */
abstract class CacheBusterAware extends AbstractExtension
{
    protected bool $cacheBuster = true;

    /**
     * prepares entries with cache buster prefix
     */
    abstract protected function prepareEntries(): void;

    public function isCacheBuster(): bool
    {
        return $this->cacheBuster;
    }

    public function setCacheBuster(bool $cacheBuster): void
    {
        $this->cacheBuster = $cacheBuster;
    }
}
