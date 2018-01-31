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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RequestContext;

class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var RequestContext
     */
    private $requestContext;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;

        $this->optionsResolver = new OptionsResolver();
        $this->configureOptions($this->optionsResolver);
    }

    protected function configureOptions(OptionsResolver $options)
    {
        $options->setDefaults([
            'scheme'   => $this->requestContext->getScheme(),
            'host'     => $this->requestContext->getHost(),
            'base_url' => $this->requestContext->getBaseUrl()
        ]);

        $options->setDefault('port', function (Options $options) {
            if ('http' === $options['scheme'] && 80 !== $this->requestContext->getHttpPort()) {
                return $this->requestContext->getHttpPort();
            }

            if ('https' === $options['scheme'] && 443 !== $this->requestContext->getHttpsPort()) {
                return $this->requestContext->getHttpsPort();
            }

            return null;
        });

        $options->setAllowedValues('scheme', ['http', 'https']);
        $options->setAllowedTypes('host', 'string');
        $options->setAllowedTypes('port', ['int', 'null']);
        $options->setAllowedTypes('base_url', 'string');
    }

    public function generateUrl(Document $document, Site $site = null, array $options = []): string
    {
        $options = $this->prepareOptions($options, $site);

        $scheme = $this->schemeFor($document, $site, $options);
        $host   = $this->hostFor($document, $site, $options);
        $port   = $this->portFor($document, $site, $options);
        $path   = $this->pathFor($document, $site, $options);

        if (!empty($port)) {
            $port = ':' . $port;
        }

        return $scheme . '://' . $host . $port . $path;
    }

    protected function prepareOptions(array $options, Site $site = null): array
    {
        // set site host as default value if it is not explicitely set via options
        if (null !== $site && !isset($options['host'])) {
            $host = $this->hostForSite($site);

            if (!empty($host)) {
                $options['host'] = $host;
            }
        }

        return $this->optionsResolver->resolve($options);
    }

    protected function schemeFor(Document $document, Site $site = null, array $options = []): string
    {
        return $options['scheme'];
    }

    protected function hostFor(Document $document, Site $site = null, array $options = []): string
    {
        if (null !== $site) {
            return $this->hostForSite($site);
        } else {
            return $options['host'];
        }
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

    protected function portFor(Document $document, Site $site = null, array $options = [])
    {
        return $options['port'];
    }

    protected function pathFor(Document $document, Site $site = null, array $options = []): string
    {
        $path = $document->getRealFullPath();

        if (null !== $site) {
            // strip site prefix from path
            $path = substr($path, strlen($site->getRootDocument()->getRealFullPath()));
        }

        $path = $options['base_url'] . $path;

        if (!empty($path)) {
            $path = '/' . ltrim($path, '/');
        }

        return $path;
    }
}
