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
use Pimcore\Sitemap\UrlGeneratorInterface;

/**
 * URL generator specific to documents with site support.
 */
class DocumentUrlGenerator implements DocumentUrlGeneratorInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function generateUrl(string $path, array $options = [])
    {
        return $this->urlGenerator->generateUrl($path, $options);
    }

    public function generateDocumentUrl(Document $document, Site $site = null, array $options = []): string
    {
        if ($document instanceof Document\Page && $document->getPrettyUrl()) {
            $prettyUrlSet = true;
            $path = $document->getPrettyUrl();
        } else {
            $prettyUrlSet = false;
            $path = $document->getRealFullPath();
        }
        if (null !== $site && !$prettyUrlSet) {
            // strip site prefix from path
            $path = substr($path, strlen($site->getRootDocument()->getRealFullPath()));
        }

        $options = $this->prepareOptions($options, $site);

        return $this->urlGenerator->generateUrl($path, $options);
    }

    protected function prepareOptions(array $options, Site $site = null): array
    {
        if (!isset($options['host'])) {
            // set site host as default value if it is not explicitely set via options
            if (null !== $site) {
                $host = $this->hostForSite($site);

                if (!empty($host)) {
                    $options['host'] = $host;
                }
            }
        }

        return $options;
    }

    protected function hostForSite(Site $site)
    {
        $host = $site->getMainDomain();
        if (!empty($host)) {
            return $host;
        }

        foreach ($site->getDomains() as $domain) {
            if (!empty($domain)) {
                $host = $domain;
                break;
            }
        }

        if (empty($host)) {
            throw new \RuntimeException(sprintf('Failed to resolve host for site %d', $site->getId()));
        }

        return $host;
    }
}
