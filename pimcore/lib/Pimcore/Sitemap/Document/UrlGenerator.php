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
use Symfony\Component\OptionsResolver\OptionsResolver;

class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    public function __construct()
    {
        $this->optionsResolver = new OptionsResolver();
        $this->configureOptions($this->optionsResolver);
    }

    protected function configureOptions(OptionsResolver $options)
    {
        $options->setDefaults([
            'defaultScheme' => 'https'
        ]);

        $options->setAllowedValues('defaultScheme', ['http', 'https']);
    }

    public function generateUrl(Document $document, Site $site = null, array $options = []): string
    {
        $options = $this->optionsResolver->resolve($options);

        $scheme = $this->schemeFor($document, $site, $options);
        $domain = $this->domainFor($document, $site, $options);
        $path   = $this->pathFor($document, $site, $options);

        return sprintf('%s://%s%s', $scheme, $domain, $path);
    }

    protected function schemeFor(Document $document, Site $site = null, array $options = []): string
    {
        return $options['defaultScheme'];
    }

    protected function domainFor(Document $document, Site $site = null, array $options = []): string
    {
        if (null !== $site) {
            $domain = $site->getMainDomain();
        } else {
            $domain = \Pimcore\Config::getSystemConfig()->general->domain;
        }

        if (empty($domain)) {
            throw new \RuntimeException(sprintf(
                'Domain for %s is not defined',
                null === $site ? 'main site' : 'site ' . $site->getId()
            ));
        }

        return $domain;
    }

    protected function pathFor(Document $document, Site $site = null, array $options = []): string
    {
        $path = $document->getRealFullPath();

        if (null !== $site) {
            // strip site prefix from path
            $path = substr($path, strlen($site->getRootDocument()->getRealFullPath()));
        }

        return $path;
    }
}
