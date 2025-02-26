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

use IteratorAggregate;
use Presta\SitemapBundle\Service\UrlContainerInterface;

/**
 * Context which is passed to every filter/processor
 */
interface GeneratorContextInterface extends IteratorAggregate, \Countable
{
    public function getUrlContainer(): UrlContainerInterface;

    public function getSection(): ?string;

    public function all(): array;

    public function keys(): array;

    public function get(int|string $key, mixed $default = null): mixed;

    public function has(int|string $key): bool;
}
