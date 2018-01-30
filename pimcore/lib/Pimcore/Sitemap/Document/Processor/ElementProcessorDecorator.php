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

namespace Pimcore\Sitemap\Document\Processor;

use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Sitemap\Document\ProcessorInterface;
use Pimcore\Sitemap\Element\ProcessorInterface as ElementProcessorInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;

/**
 * Decorates a standard element processor and makes it available as filter for the DocumentTreeGenerator
 */
class ElementProcessorDecorator implements ProcessorInterface
{
    /**
     * @var ElementProcessorInterface
     */
    private $processor;

    public function __construct(ElementProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    public function process(Url $url, Document $document, Site $site = null)
    {
        return $this->processor->process($url, $document);
    }
}
