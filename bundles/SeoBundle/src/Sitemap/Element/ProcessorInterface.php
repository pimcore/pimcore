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

namespace Pimcore\Bundle\SeoBundle\Sitemap\Element;

use Pimcore\Model\Element\ElementInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;

interface ProcessorInterface
{
    /**
     * Processes an URL. The processor is expected to return the same or a new URL instance or null
     *
     *
     */
    public function process(Url $url, ElementInterface $element, GeneratorContextInterface $context): ?Url;
}
