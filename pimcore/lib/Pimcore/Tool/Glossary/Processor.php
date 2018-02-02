<?php

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

namespace Pimcore\Tool\Glossary;

use Pimcore\Cache;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Glossary;
use Pimcore\Model\Site;

class Processor
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var EditmodeResolver
     */
    private $editmodeResolver;

    /**
     * @var DocumentResolver
     */
    private $documentResolver;

    /**
     * @var array
     */
    private $blockedTags = [
        'a', 'script', 'style', 'code', 'pre', 'textarea', 'acronym',
        'abbr', 'option', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
    ];

    /**
     * @param RequestHelper $requestHelper
     * @param EditmodeResolver $editmodeResolver
     * @param DocumentResolver $documentResolver
     */
    public function __construct(
        RequestHelper $requestHelper,
        EditmodeResolver $editmodeResolver,
        DocumentResolver $documentResolver
    ) {
        $this->requestHelper    = $requestHelper;
        $this->editmodeResolver = $editmodeResolver;
        $this->documentResolver = $documentResolver;
    }

    /**
     * Process glossary entries in content string
     *
     * @param string $content
     *
     * @return string
     */
    public function process(string $content): string
    {
        $data = $this->getData();
        if (empty($data)) {
            return $content;
        }

        if ($this->editmodeResolver->isEditmode()) {
            return $content;
        }

        // why not using a simple str_ireplace(array(), array(), $subject) ?
        // because if you want to replace the terms "Donec vitae" and "Donec" you will get nested links, so the content of the html must be reloaded every searchterm to ensure that there is no replacement within a blocked tag
        include_once(PIMCORE_PATH . '/lib/simple_html_dom.php');

        // kind of a hack but,
        // changed to this because of that: http://www.pimcore.org/issues/browse/PIMCORE-687
        $html = str_get_html($content);
        if (!$html) {
            return $content;
        }

        $es = $html->find('text');

        $tmpData = [
            'search'      => [],
            'replace'     => [],
            'placeholder' => []
        ];

        // get initial document from request (requested document, if it was a "document" request)
        $currentDocument = $this->documentResolver->getDocument();

        foreach ($data as $entry) {
            if ($currentDocument && $currentDocument instanceof Document) {
                // check if the current document is the target link (id check)
                if ($entry['linkType'] == 'internal' && $currentDocument->getId() == $entry['linkTarget']) {
                    continue;
                }

                // check if the current document is the target link (path check)
                if ($currentDocument->getFullPath() == rtrim($entry['linkTarget'], ' /')) {
                    continue;
                }
            }

            $tmpData['search'][]  = $entry['search'];
            $tmpData['replace'][] = $entry['replace'];
        }

        $data = $tmpData;

        $data['placeholder'] = [];
        for ($i = 0; $i < count($data['search']); $i++) {
            $data['placeholder'][] = '%%' . uniqid($i, true) . '%%';
        }

        foreach ($es as $e) {
            if (!in_array((string)$e->parent()->tag, $this->blockedTags)) {
                $e->innertext = preg_replace($data['search'], $data['placeholder'], $e->innertext);
                $e->innertext = str_replace($data['placeholder'], $data['replace'], $e->innertext);
            }
        }

        $result = $html->save();

        $html->clear();
        unset($html);

        return $result;
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        $locale = $this->requestHelper->getMasterRequest()->getLocale();
        if (!$locale) {
            return [];
        }

        $siteId = '';
        if (Site::isSiteRequest()) {
            $siteId = Site::getCurrentSite()->getId();
        }

        $cacheKey = 'glossary_' . $locale . '_' . $siteId;

        if (Cache\Runtime::isRegistered($cacheKey)) {
            return Cache\Runtime::get($cacheKey);
        }

        if (!$data = Cache::load($cacheKey)) {
            $list = new Glossary\Listing();
            $list->setCondition("(language = ? OR language IS NULL OR language = '') AND (site = ? OR site IS NULL OR site = '')", [$locale, $siteId]);
            $list->setOrderKey('LENGTH(`text`)', false);
            $list->setOrder('DESC');

            $data = $list->getDataArray();
            $data = $this->prepareData($data);

            Cache::save($data, $cacheKey, ['glossary'], null, 995);
            Cache\Runtime::set($cacheKey, $data);
        }

        return $data;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function prepareData(array $data): array
    {
        $mappedData = [];

        // fix htmlentities issues
        $tmpData = [];
        foreach ($data as $d) {
            if ($d['text'] != htmlentities($d['text'], null, 'UTF-8')) {
                $td         = $d;
                $td['text'] = htmlentities($d['text'], null, 'UTF-8');
                $tmpData[]  = $td;
            }

            $tmpData[] = $d;
        }

        $data = $tmpData;

        // prepare data
        foreach ($data as $d) {
            if (!($d['link'] || $d['abbr'] || $d['acronym'])) {
                continue;
            }

            $r = $d['text'];
            if ($d['abbr']) {
                $r = '<abbr class="pimcore_glossary" title="' . $d['abbr'] . '">' . $r . '</abbr>';
            } elseif ($d['acronym']) {
                $r = '<acronym class="pimcore_glossary" title="' . $d['acronym'] . '">' . $r . '</acronym>';
            }

            $linkType   = '';
            $linkTarget = '';

            if ($d['link']) {
                $linkType   = 'external';
                $linkTarget = $d['link'];

                if (intval($d['link'])) {
                    if ($doc = Document::getById($d['link'])) {
                        $d['link'] = $doc->getFullPath();

                        $linkType   = 'internal';
                        $linkTarget = $doc->getId();
                    }
                }

                $r = '<a class="pimcore_glossary" href="' . $d['link'] . '">' . $r . '</a>';
            }

            // add PCRE delimiter and modifiers
            if ($d['exactmatch']) {
                $d['text'] = "/(?<!\w)" . preg_quote($d['text'], '/') . "(?!\w)/";
            } else {
                $d['text'] = '/' . preg_quote($d['text'], '/') . '/';
            }

            if (!$d['casesensitive']) {
                $d['text'] .= 'i';
            }

            $mappedData[] = [
                'replace'    => $r,
                'search'     => $d['text'],
                'linkType'   => $linkType,
                'linkTarget' => $linkTarget
            ];
        }

        return $mappedData;
    }
}
