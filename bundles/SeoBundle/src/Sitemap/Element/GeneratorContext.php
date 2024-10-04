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

namespace Pimcore\Bundle\SeoBundle\Sitemap\Element;

use ArrayIterator;
use Iterator;
use Presta\SitemapBundle\Service\UrlContainerInterface;

class GeneratorContext implements GeneratorContextInterface
{
    private UrlContainerInterface $urlContainer;

    private ?string $section = null;

    private array $parameters = [];

    public function __construct(UrlContainerInterface $urlContainer, string $section = null, array $parameters = [])
    {
        $this->urlContainer = $urlContainer;
        $this->section = $section;
        $this->parameters = $parameters;
    }

    public function getUrlContainer(): UrlContainerInterface
    {
        return $this->urlContainer;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    public function get(int|string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
    }

    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->parameters);
    }

    public function count(): int
    {
        return count($this->parameters);
    }
}
