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

namespace Pimcore\Bundle\GlossaryBundle\Tool;

use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Cache;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tool\DomCrawler;

/**
 * @internal
 */
class Processor
{
    private RequestHelper $requestHelper;

    private EditmodeResolver $editmodeResolver;

    private DocumentResolver $documentResolver;

    private array $blockedTags = [];

    public function __construct(
        RequestHelper $requestHelper,
        EditmodeResolver $editmodeResolver,
        DocumentResolver $documentResolver,
        array $blockedTags = [],
    ) {
        $this->requestHelper = $requestHelper;
        $this->editmodeResolver = $editmodeResolver;
        $this->documentResolver = $documentResolver;
        $this->blockedTags = $blockedTags;
    }

    /**
     * Process glossary entries in content string
     *
     *
     */
    public function process(string $content, array $options): string
    {
        if ($this->editmodeResolver->isEditmode()) {
            return $content;
        }

        $locale = $this->requestHelper->getMainRequest()->getLocale();
        $currentDocument = $this->documentResolver->getDocument();
        $uri = $this->requestHelper->getMainRequest()->getRequestUri();

        return $this->parse($content, $options, $locale, $currentDocument, $uri);
    }

    public function parse(string $content, array $options, string $locale, ?Document $document, ?string $uri): string
    {
        $data = $this->getData($locale);
        if (empty($data)) {
            return $content;
        }

        $options = array_merge([
            'limit' => -1,
        ], $options);

        // why not using a simple str_ireplace(array(), array(), $subject) ?
        // because if you want to replace the terms "Donec vitae" and "Donec" you will get nested links, so the content
        // of the html must be reloaded every search term to ensure that there is no replacement within a blocked tag
        $html = new DomCrawler($content);
        $es = $html->filterXPath('//*[normalize-space(text())]');

        $tmpData = [
            'search' => [],
            'replace' => [],
        ];

        foreach ($data as $entry) {
            $linkTarget = $entry['linkTarget'] ?? '';
            $linkTargetTrimmed = rtrim((string)$linkTarget, ' /');
            if ($document instanceof Document) {
                // check if the current document is the target link (id check)
                if ($entry['linkType'] == 'internal' && $document->getId() == $linkTarget) {
                    continue;
                }

                // check if the current document is the target link (path check)
                if ($document->getFullPath() == $linkTargetTrimmed) {
                    continue;
                }
            }

            // check if the current URI is the target link (path check)
            if ($uri === $linkTargetTrimmed) {
                continue;
            }

            $tmpData['search'][] = $entry['search'];
            $tmpData['replace'][] = $entry['replace'];
        }

        $data = $tmpData;
        $data['count'] = array_fill(0, count($data['search']), 0);

        $es->each(function ($parentNode, $i) use ($options, $data) {
            /** @var DomCrawler|null $parentNode */
            $text = $parentNode->html();
            if (
                $parentNode instanceof DomCrawler &&
                !in_array($parentNode->nodeName(), $this->blockedTags) &&
                strlen(trim($text))
            ) {
                $originalText = $text;
                if ($options['limit'] < 0) {
                    $text = preg_replace($data['search'], $data['replace'], $text);
                } else {
                    foreach ($data['search'] as $index => $search) {
                        if ($data['count'][$index] < $options['limit']) {
                            $limit = $options['limit'] - $data['count'][$index];
                            $text = preg_replace($search, $data['replace'][$index], $text, $limit, $count);
                            $data['count'][$index] += $count;
                        }
                    }
                }

                if ($originalText !== $text) {
                    $domNode = $parentNode->getNode(0);
                    $fragment = $domNode->ownerDocument->createDocumentFragment();
                    $fragment->appendXML(htmlentities($text, ENT_XML1));
                    $clone = $domNode->cloneNode();
                    $clone->appendChild($fragment);
                    $domNode->parentNode->replaceChild($clone, $domNode);
                }
            }
        });

        $result = html_entity_decode($html->html(), ENT_XML1);
        $html->clear();
        unset($html);

        return $result;
    }

    private function getData(string $locale): array
    {
        $siteId = '';
        if (Site::isSiteRequest()) {
            $siteId = Site::getCurrentSite()->getId();
        }

        $cacheKey = 'glossary_' . $locale . '_' . $siteId;

        if (Cache\RuntimeCache::isRegistered($cacheKey)) {
            return Cache\RuntimeCache::get($cacheKey);
        }

        if (!$data = Cache::load($cacheKey)) {
            $list = new Glossary\Listing();
            $list->setCondition("(language = ? OR language IS NULL OR language = '') AND (site = ? OR site IS NULL OR site = '')", [$locale, $siteId]);
            $list->setOrderKey('LENGTH(`text`)', false);
            $list->setOrder('DESC');

            $data = $list->getDataArray();
            $data = $this->prepareData($data);

            Cache::save($data, $cacheKey, ['glossary'], null, 995);
            Cache\RuntimeCache::set($cacheKey, $data);
        }

        return $data;
    }

    private function prepareData(array $data): array
    {
        $mappedData = [];

        // fix htmlentities issues
        $tmpData = [];
        foreach ($data as $d) {
            if (!($d['text'])) {
                continue;
            }
            $text = htmlentities($d['text'], ENT_COMPAT, 'UTF-8');
            if ($d['text'] !== $text) {
                $td = $d;
                $td['text'] = $text;
                $tmpData[] = $td;
            }

            $tmpData[] = $d;
        }

        $data = $tmpData;

        // prepare data
        foreach ($data as $d) {
            if (!($d['link'] || $d['abbr'])) {
                continue;
            }

            $r = $d['text'];
            if ($d['abbr']) {
                $r = '<abbr class="pimcore_glossary" title="' . $d['abbr'] . '">' . $r . '</abbr>';
            }

            $linkType = '';
            $linkTarget = '';

            if ($d['link']) {
                $linkType = 'external';
                $linkTarget = $d['link'];

                if ((int)$d['link']) {
                    if ($doc = Document::getById($d['link'])) {
                        $d['link'] = $doc->getFullPath();

                        $linkType = 'internal';
                        $linkTarget = $doc->getId();
                    }
                }

                $r = '<a class="pimcore_glossary" href="' . $d['link'] . '">' . $r . '</a>';
            }

            // add PCRE delimiter and modifiers
            if ($d['exactmatch']) {
                $d['text'] = '/<a.*?\/a>(*SKIP)(*FAIL)|(?<!\w)' . preg_quote($d['text'], '/') . '(?!\w)/';
            } else {
                $d['text'] = '/<a.*?\/a>(*SKIP)(*FAIL)|' . preg_quote($d['text'], '/') . '/';
            }

            if (!$d['casesensitive']) {
                $d['text'] .= 'i';
            }

            $mappedData[] = [
                'replace' => $r,
                'search' => $d['text'],
                'linkType' => $linkType,
                'linkTarget' => $linkTarget,
            ];
        }

        return $mappedData;
    }
}
