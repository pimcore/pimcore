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

namespace Pimcore\Bundle\SeoBundle\Sitemap\Document;

use Exception;
use Generator;
use Pimcore;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\AbstractElementGenerator;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentTreeGenerator extends AbstractElementGenerator
{
    private DocumentUrlGeneratorInterface $urlGenerator;

    protected array $options = [];

    private int $currentBatchCount = 0;

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

    protected function configureOptions(OptionsResolver $options): void
    {
        $options->setDefaults([
            'rootId' => 1,
            'handleMainDomain' => true,
            'handleCurrentSite' => false,
            'handleSites' => true,
            'urlGeneratorOptions' => [],
            'garbageCollectThreshold' => 50,
        ]);

        $options->setAllowedTypes('rootId', 'int');
        $options->setAllowedTypes('handleMainDomain', 'bool');
        $options->setAllowedTypes('handleCurrentSite', 'bool');
        $options->setAllowedTypes('handleSites', 'bool');
        $options->setAllowedTypes('urlGeneratorOptions', 'array');
        $options->setAllowedTypes('garbageCollectThreshold', 'int');
    }

    public function populate(UrlContainerInterface $urlContainer, string $section = null): void
    {
        if ($this->options['handleMainDomain'] && (null === $section || $section === 'default')) {
            $rootDocument = Document::getById($this->options['rootId']);

            if ($rootDocument instanceof Document) {
                $this->populateCollection($urlContainer, $rootDocument, 'default');
            }
        }

        if ($this->options['handleCurrentSite']) {
            try {
                $currentSite = Site::getCurrentSite();
                $rootDocument = $currentSite->getRootDocument();
                if ($rootDocument instanceof Document) {
                    $siteSection = sprintf('site_%s', $currentSite->getId());
                    $this->populateCollection($urlContainer, $rootDocument, $siteSection, $currentSite);
                }
            } catch (Exception $e) {
                Logger::error('Cannot determine current domain for sitemap generation');
            }
        }

        if ($this->options['handleSites']) {
            $sites = (new Site\Listing())->load();
            foreach ($sites as $site) {
                $siteSection = sprintf('site_%s', $site->getId());

                if (null === $section || $section === $siteSection) {
                    $this->populateCollection($urlContainer, $site->getRootDocument(), $siteSection, $site);
                }
            }
        }
    }

    private function populateCollection(UrlContainerInterface $urlContainer, Document $rootDocument, string $section, Site $site = null): void
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

    private function createUrl(Document $document, DocumentGeneratorContext $context): ?Url
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
     * @throws Exception
     */
    private function visit(Document $document, DocumentGeneratorContext $context): Generator
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
                Pimcore::collectGarbage();
            }
        }

        if ($document->hasChildren() && $this->handlesChildren($document, $context)) {
            foreach ($document->getChildren() as $child) {
                yield from $this->visit($child, $context);
            }
        }
    }
}
