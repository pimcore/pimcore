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
