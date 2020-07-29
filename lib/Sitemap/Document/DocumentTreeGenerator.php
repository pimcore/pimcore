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
use Pimcore\Sitemap\Element\AbstractElementGenerator;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentTreeGenerator extends AbstractElementGenerator
{
    /**
     * @var DocumentUrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var int
     */
    private $currentBatchCount = 0;

    public function __construct(
        DocumentUrlGeneratorInterface $urlGenerator,
        array $filters = [],
        array $processors = [],
        array $options = []
    ) {
        parent::__construct($filters, $processors);

        $this->urlGenerator = $urlGenerator;

        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);

        $this->options = $optionsResolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $options)
    {
        $options->setDefaults([
            'rootId' => 1,
            'handleMainDomain' => true,
            'handleSites' => true,
            'urlGeneratorOptions' => [],
            'garbageCollectThreshold' => 50,
        ]);

        $options->setAllowedTypes('rootId', 'int');
        $options->setAllowedTypes('handleMainDomain', 'bool');
        $options->setAllowedTypes('handleSites', 'bool');
        $options->setAllowedTypes('urlGeneratorOptions', 'array');
        $options->setAllowedTypes('garbageCollectThreshold', 'int');
    }

    public function populate(UrlContainerInterface $urlContainer, string $section = null)
    {
        if ($this->options['handleMainDomain'] && null === $section || $section === 'default') {
            $rootDocument = Document::getById($this->options['rootId']);

            $this->populateCollection($urlContainer, $rootDocument, 'default');
        }

        if ($this->options['handleSites']) {
            /** @var Site[] $sites */
            $sites = (new Site\Listing())->load();
            foreach ($sites as $site) {
                $siteSection = sprintf('site_%s', $site->getId());

                if (null === $section || $section === $siteSection) {
                    $this->populateCollection($urlContainer, $site->getRootDocument(), $siteSection, $site);
                }
            }
        }
    }

    private function populateCollection(UrlContainerInterface $urlContainer, Document $rootDocument, string $section, Site $site = null)
    {
        $context = new DocumentGeneratorContext($urlContainer, $section, $site);
        $visit = $this->visit($rootDocument, $context);

        foreach ($visit as $document) {
            $url = $this->createUrl($document, $context);
            if (null === $url) {
                continue;
            }

            $urlContainer->addUrl($url, $section);
        }
    }

    /**
     * @param Document $document
     * @param DocumentGeneratorContext $context
     *
     * @return Url|null
     */
    private function createUrl(Document $document, DocumentGeneratorContext $context)
    {
        $url = $this->urlGenerator->generateDocumentUrl(
            $document,
            $context->getSite(),
            $this->options['urlGeneratorOptions']
        );

        $url = new UrlConcrete($url);
        $url = $this->process($url, $document, $context);

        return $url;
    }

    /**
     * @param Document $document
     * @param DocumentGeneratorContext $context
     *
     * @return \Generator|Document[]
     *
     * @throws \Exception
     */
    private function visit(Document $document, DocumentGeneratorContext $context): \Generator
    {
        if ($document instanceof Document\Hardlink) {
            $document = Document\Hardlink\Service::wrap($document);
            if (empty($document)) {
                return;
            }
        }

        if ($this->canBeAdded($document, $context)) {
            yield $document;

            if (++$this->currentBatchCount >= $this->options['garbageCollectThreshold']) {
                $this->currentBatchCount = 0;
                \Pimcore::collectGarbage();
            }
        }

        if ($document->hasChildren() && $this->handlesChildren($document, $context)) {
            foreach ($document->getChildren() as $child) {
                yield from $this->visit($child, $context);
            }
        }
    }
}
