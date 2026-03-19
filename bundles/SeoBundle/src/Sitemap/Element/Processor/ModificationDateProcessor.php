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

namespace Pimcore\Bundle\SeoBundle\Sitemap\Element\Processor;

use DateTime;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\ProcessorInterface;
use Pimcore\Model\Element\ElementInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

/**
 * Adds modification date from element modification date.
 */
class ModificationDateProcessor implements ProcessorInterface
{
    public function process(Url $url, ElementInterface $element, GeneratorContextInterface $context): Url|UrlConcrete|null
    {
        if (!$url instanceof UrlConcrete) {
            return $url;
        }

        $url->setLastmod(DateTime::createFromFormat('U', (string)$element->getModificationDate()));

        return $url;
    }
}
