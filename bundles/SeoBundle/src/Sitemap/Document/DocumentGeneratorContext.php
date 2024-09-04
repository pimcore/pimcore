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

use InvalidArgumentException;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContext;
use Pimcore\Model\Site;
use Presta\SitemapBundle\Service\UrlContainerInterface;

class DocumentGeneratorContext extends GeneratorContext
{
    public function __construct(
        UrlContainerInterface $urlContainer,
        string $section = null,
        Site $site = null,
        array $parameters = []
    ) {
        if (null !== $site) {
            $parameters['site'] = $site;
        }

        if (isset($parameters['site']) && !$parameters['site'] instanceof Site) {
            throw new InvalidArgumentException(sprintf('Site parameter must be an instance of %s', Site::class));
        }

        parent::__construct($urlContainer, $section, $parameters);
    }

    public function hasSite(): bool
    {
        return $this->has('site');
    }

    public function getSite(): ?Site
    {
        return $this->get('site');
    }
}
