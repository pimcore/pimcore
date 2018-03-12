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

namespace AppBundle\LinkGenerator;

use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\News;
use Pimcore\Model\Document;
use Pimcore\Tool;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RouterInterface;

class NewsLinkGenerator implements LinkGeneratorInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $prefixes = [];

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function generate(Concrete $object, array $params = []): string
    {
        if (!$object instanceof News) {
            throw new \InvalidArgumentException(sprintf('Object must be an instance of %s', News::class));
        }

        $language = $this->resolveLanguage($params);
        $prefix   = $this->getPrefix($language);

        $routeParams = [
            'id'     => $object->getId(),
            'text'   => $object->getTitle($language),
            'prefix' => $prefix,
        ];

        $referenceType = $params['referenceType'] ?? UrlGenerator::ABSOLUTE_PATH;

        return $this->router->generate('news', $routeParams, $referenceType);
    }

    /**
     * Resolves language to use from multiple locations. Uses a language parameter if
     * set and falls back to _locale from the request and the website default language.
     *
     * @param array $params
     *
     * @return string
     */
    private function resolveLanguage(array $params): string
    {
        $language = null;
        if (isset($params['language'])) {
            $language = $params['language'];
        } else {
            $locale = $this->router->getContext()->getParameter('_locale');
            if (!empty($locale)) {
                $language = $locale;
            }
        }

        if (empty($language)) {
            $language = Tool::getDefaultLanguage();
        }

        if (empty($language)) {
            throw new \RuntimeException('Unable to resolve language for News link generator.');
        }

        return $language;
    }

    /**
     * Builds prefix for a language by fetching the language root (e.g. /en) and reading the "news" property from there
     * which points to the document containing the news and acting as prefix.
     *
     * @param string $language
     *
     * @return mixed
     */
    private function getPrefix(string $language)
    {
        if (isset($this->prefixes[$language])) {
            return $this->prefixes[$language];
        }

        $languageRoot = Document::getByPath('/' . $language);
        if (!$languageRoot) {
            throw new \RuntimeException(sprintf('Failed to find language root for language "%s"', $language));
        }

        $newsDocument = $languageRoot->getProperty('news');
        if (!$newsDocument instanceof Document) {
            throw new \RuntimeException(sprintf('Failed to find news document for language "%s"', $language));
        }

        $this->prefixes[$language] = $newsDocument->getFullPath();

        return $this->prefixes[$language];
    }
}
