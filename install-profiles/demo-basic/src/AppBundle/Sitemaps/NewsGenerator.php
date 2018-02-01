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

namespace AppBundle\Sitemaps;

use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\News;
use Pimcore\Sitemap\Element\AbstractElementGenerator;
use Pimcore\Sitemap\Element\GeneratorContext;
use Pimcore\Tool;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NewsGenerator extends AbstractElementGenerator
{
    /**
     * @var LinkGeneratorInterface
     */
    private $linkGenerator;

    public function populate(UrlContainerInterface $urlContainer, string $section = null)
    {
        if (null !== $section && $section !== 'news') {
            // do not add entries if section doesn't match
            return;
        }

        $section = 'news';

        $newsList = new News\Listing();
        $newsList->setOrderKey('date');
        $newsList->setOrder('DESC');

        $languages = Tool::getValidLanguages();

        /** @var News $news */
        foreach ($newsList as $news) {
            foreach ($languages as $language) {
                // context contains metadata which can be consumed from filters
                // e.g. with this context it would be possible to filter entries by
                // language
                $context = new GeneratorContext($urlContainer, $section, [
                    'language' => $language
                ]);

                // only add element if it is not filtered
                if (!$this->canBeAdded($news, $context)) {
                    continue;
                }

                $url = $this->generateUrl($news, $language);

                // run URL through registered processors
                $url = $this->process($url, $news, $context);

                if (null === $url) {
                    continue;
                }

                $urlContainer->addUrl($url, $section);
            }
        }
    }

    /**
     * Generates an Url object which can be added to the sitemap
     *
     * @param News $news
     * @param string $language
     *
     * @return Url|null
     */
    private function generateUrl(News $news, string $language)
    {
        if (null === $this->linkGenerator) {
            $this->linkGenerator = $news->getClass()->getLinkGenerator();

            if (null === $this->linkGenerator) {
                throw new \RuntimeException('Link generator for News class is not defined.');
            }
        }

        $url = $this->linkGenerator->generate($news, [
            'language'      => $language,
            'referenceType' => UrlGeneratorInterface::ABSOLUTE_URL
        ]);

        return new UrlConcrete($url);
    }
}
