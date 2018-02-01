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

namespace Pimcore\Sitemap\Element;

use Presta\SitemapBundle\Service\UrlContainerInterface;

/**
 * Context which is passed to every filter/processor
 */
interface GeneratorContextInterface extends \IteratorAggregate, \Countable
{
    public function getUrlContainer(): UrlContainerInterface;

    /**
     * @return string|null
     */
    public function getSection();

    public function all(): array;

    public function keys(): array;

    public function get($key, $default = null);

    public function has($key): bool;
}
