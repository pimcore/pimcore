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

namespace Pimcore\Sitemap\Element\Processor;

use Pimcore\Model\Element\AbstractElement;
use Pimcore\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Sitemap\Element\ProcessorInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

/**
 * Adds modification date from element modification date.
 */
class ModificationDateProcessor implements ProcessorInterface
{
    public function process(Url $url, AbstractElement $element, GeneratorContextInterface $context)
    {
        if (!$url instanceof UrlConcrete) {
            return $url;
        }

        $url->setLastmod(\DateTime::createFromFormat('U', (string)$element->getModificationDate()));

        return $url;
    }
}
