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

namespace AppBundle\Sitemaps\Processor;

use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\News;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Sitemap\Element\ProcessorInterface;
use Pimcore\Sitemap\UrlGeneratorInterface;
use Presta\SitemapBundle\Sitemap\Url\GoogleImage;
use Presta\SitemapBundle\Sitemap\Url\GoogleImageUrlDecorator;
use Presta\SitemapBundle\Sitemap\Url\Url;

/**
 * Adds google image entries to news entries.
 *
 * See https://github.com/prestaconcept/PrestaSitemapBundle/blob/master/Resources/doc/6-Url_Decorator.md for details.
 */
class NewsImageProcessor implements ProcessorInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function process(Url $url, AbstractElement $element, GeneratorContextInterface $context)
    {
        if (!$element instanceof News) {
            return $url;
        }

        /** @var Image[] $images */
        $images = [
            $element->getImage_1(),
            $element->getImage_2(),
            $element->getImage_3(),
        ];

        // filter nulls
        $images = array_filter($images);
        if (empty($images)) {
            return $url;
        }

        $language = $context->get('language');

        $urlImage = new GoogleImageUrlDecorator($url);
        foreach ($images as $image) {
            $urlImage->addImage($this->createGoogleImage($image, $language));
        }

        return $urlImage;
    }

    private function createGoogleImage(Image $image, string $language = null): GoogleImage
    {
        $path = $image->getThumbnail()->getPath();

        $url = $this->urlGenerator->generateUrl($path);
        $url = new GoogleImage($url);

        $title = $image->getMetadata('title', $language);
        if (!empty($title)) {
            $url->setTitle($title);
        }

        return $url;
    }
}
