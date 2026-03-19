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

use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\ProcessorInterface;
use Pimcore\Model\Element\ElementInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

/**
 * Adds change frequency and priority entries based on document properties.
 */
class PropertiesProcessor implements ProcessorInterface
{
    const PROPERTY_CHANGE_FREQUENCY = 'sitemaps_changefreq';

    const PROPERTY_PRIORITY = 'sitemaps_priority';

    public function process(Url $url, ElementInterface $element, GeneratorContextInterface $context): Url|UrlConcrete|null
    {
        if (!$url instanceof UrlConcrete) {
            return $url;
        }

        $changeFreq = $element->getProperty(self::PROPERTY_CHANGE_FREQUENCY);
        if (!empty($changeFreq)) {
            $url->setChangefreq($changeFreq);
        }

        $priority = $element->getProperty(self::PROPERTY_PRIORITY);
        if (!empty($priority)) {
            $url->setPriority($priority);
        }

        return $url;
    }
}
