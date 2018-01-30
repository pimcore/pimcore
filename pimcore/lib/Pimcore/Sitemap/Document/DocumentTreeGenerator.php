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

namespace Pimcore\Sitemap\Document;

use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Sitemap\Document\Filter\ElementFilterDecorator;
use Pimcore\Sitemap\Document\Processor\ElementProcessorDecorator;
use Pimcore\Sitemap\Element\FilterInterface as ElementFilterInterface;
use Pimcore\Sitemap\Element\ProcessorInterface as ElementProcessorInterface;
use Pimcore\Sitemap\GeneratorInterface;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentTreeGenerator implements GeneratorInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var FilterInterface[]
     */
    private $filters = [];

    /**
     * @var ProcessorInterface[]
     */
    private $processors = [];

    /**
     * @var int
     */
    private $currentBatchCount = 0;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        array $filters = [],
        array $processors = [],
        array $options = []
    )
    {
        $this->urlGenerator = $urlGenerator;

        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }

        foreach ($processors as $processor) {
            $this->addProcessor($processor);
        }

        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);

        $this->options = $optionsResolver->resolve($options);
    }

    public function addFilter($filter)
    {
        // wrap filter in ElementFilterDecorator if an element filter was passed
        if ($filter instanceof ElementFilterInterface) {
            $filter = new ElementFilterDecorator($filter);
        }

        if (!$filter instanceof FilterInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Filter needs to implement "%s" or "%s"',
                FilterInterface::class,
                ElementFilterInterface::class
            ));
        }

        $this->filters[] = $filter;
    }

    public function addProcessor($processor)
    {
        // wrap processor in ElementProcessorDecorator if an element processor was passed
        if ($processor instanceof ElementProcessorInterface) {
            $processor = new ElementProcessorDecorator($processor);
        }

        if (!$processor instanceof ProcessorInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Processor needs to implement "%s" or "%s"',
                ProcessorInterface::class,
                ElementProcessorInterface::class
            ));
        }

        $this->processors[] = $processor;
    }

    protected function configureOptions(OptionsResolver $options)
    {
        $options->setDefaults([
            'rootId'                  => 1,
            'handleMainDomain'        => true,
            'handleSites'             => true,
            'urlGeneratorOptions'     => [],
            'garbageCollectThreshold' => 50,
        ]);

        $options->setAllowedTypes('rootId', 'int');
        $options->setAllowedTypes('handleMainDomain', 'bool');
        $options->setAllowedTypes('handleSites', 'bool');
        $options->setAllowedTypes('urlGeneratorOptions', 'array');
        $options->setAllowedTypes('garbageCollectThreshold', 'int');
    }

    public function populate(UrlContainerInterface $container, string $section = null)
    {
        if ($this->options['handleMainDomain'] && null === $section || $section === 'default') {
            $rootDocument = Document::getById($this->options['rootId']);

            $this->populateCollection($container, $rootDocument, 'default');
        }

        if ($this->options['handleSites']) {
            /** @var Site[] $sites */
            $sites = (new Site\Listing())->load();
            foreach ($sites as $site) {
                $siteSection = sprintf('site_%s', $site->getId());

                if (null === $section || $section === $siteSection) {
                    $this->populateCollection($container, $site->getRootDocument(), $siteSection, $site);
                }
            }
        }
    }

    private function populateCollection(UrlContainerInterface $container, Document $rootDocument, string $section, Site $site = null)
    {
        $visit = $this->visit($rootDocument, $site);

        foreach ($visit as $document) {
            $url = $this->createUrl($document, $site);
            if (null === $url) {
                continue;
            }

            $container->addUrl($url, $section);
        }
    }

    private function createUrl(Document $document, Site $site = null)
    {
        $url = new UrlConcrete($this->urlGenerator->generateUrl($document, $site, $this->options['urlGeneratorOptions']));

        foreach ($this->processors as $processor) {
            $url = $processor->process($url, $document, $site);

            if (null === $url) {
                break;
            }
        }

        return $url;
    }

    /**
     * @param Document $document
     * @param Site|null $site
     *
     * @return \Generator|Document[]
     * @throws \Exception
     */
    private function visit(Document $document, Site $site = null): \Generator
    {
        if ($document instanceof Document\Hardlink) {
            $document = Document\Hardlink\Service::wrap($document);
        }

        if ($this->canBeAdded($document, $site)) {
            yield $document;

            if (++$this->currentBatchCount >= $this->options['garbageCollectThreshold']) {
                $this->currentBatchCount = 0;
                \Pimcore::collectGarbage();
            }
        }

        if ($this->handlesChildren($document, $site)) {
            foreach ($document->getChildren() as $child) {
                yield from $this->visit($child);
            }
        }
    }

    private function canBeAdded(Document $document, Site $site = null): bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->canBeAdded($document, $site)) {
                return false;
            }
        }

        return true;
    }

    private function handlesChildren(Document $document, Site $site = null): bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->handlesChildren($document, $site)) {
                return false;
            }
        }

        return true;
    }
}
