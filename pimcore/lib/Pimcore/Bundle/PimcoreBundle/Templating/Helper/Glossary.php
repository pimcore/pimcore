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

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver;
use Pimcore\Bundle\PimcoreBundle\Service\Request\EditmodeResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Templating\Helper\Helper;
use Pimcore\Cache as CacheManger;

class Glossary extends Helper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DocumentResolver
     */
    protected $documentResolverService;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    public function __construct(DocumentResolver $documentResolverService, EditmodeResolver $editmodeResolver, RequestHelper $requestHelper)
    {
        $this->documentResolverService = $documentResolverService;
        $this->editmodeResolver = $editmodeResolver;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'glossary';
    }

    public function start()
    {
        ob_start();
    }

    /**
     *
     */
    public function stop()
    {
        $contents = ob_get_clean();

        $data = $this->getData();

        $enabled = true;

        if ($this->editmodeResolver->isEditmode()) {
            $enabled = false;
        }

        if (!empty($data) && $enabled) {
            // replace

            $blockedTags = ["a", "script", "style", "code", "pre", "textarea", "acronym", "abbr", "option", "h1", "h2", "h3", "h4", "h5", "h6"];

            // why not using a simple str_ireplace(array(), array(), $subject) ?
            // because if you want to replace the terms "Donec vitae" and "Donec" you will get nested links, so the content of the html must be reloaded every searchterm to ensure that there is no replacement within a blocked tag

            include_once(PIMCORE_PATH . "/lib/simple_html_dom.php");

            // kind of a hack but,
            // changed to this because of that: http://www.pimcore.org/issues/browse/PIMCORE-687
            $html = str_get_html($contents);
            if (!$html) {
                return $contents;
            }

            $es = $html->find('text');

            $tmpData = [
                "search" => [],
                "replace" => [],
                "placeholder" => []
            ];


            // get initial document out of the front controller (requested document, if it was a "document" request)
            $currentDocument = $this->documentResolverService->getDocument();

            foreach ($data as $entry) {

                // check if the current document is the target link (id check)
                if ($currentDocument instanceof Document && $entry["linkType"] == "internal" && $currentDocument->getId() == $entry["linkTarget"]) {
                    continue;
                }

                // check if the current document is the target link (path check)
                if ($currentDocument instanceof Document && $currentDocument->getFullPath() == rtrim($entry["linkTarget"], " /")) {
                    continue;
                }


                $tmpData["search"][] = $entry["search"];
                $tmpData["replace"][] = $entry["replace"];
            }
            $data = $tmpData;

            $data["placeholder"] = [];
            for ($i = 0; $i < count($data["search"]); $i++) {
                $data["placeholder"][] = '%%' . uniqid($i, true) . '%%';
            }

            foreach ($es as $e) {
                if (!in_array((string) $e->parent()->tag, $blockedTags)) {
                    $e->innertext = preg_replace($data["search"], $data["placeholder"], $e->innertext);
                    $e->innertext = str_replace($data["placeholder"], $data["replace"], $e->innertext);
                }
            }
            echo $html->save();

            $html->clear();
            unset($html);

            // very memory intensive method with a huge amount of glossary entries
            /*foreach ($data as $search => $replace) {
                $html = str_get_html($contents);
                $es = $html->find('text');
                foreach ($es as $e) {
                    if(!in_array((string) $e->parent()->tag,$blockedTags)) {
                        $e->innertext = str_ireplace($search, $replace, $e->innertext);
                    }
                }
                $contents = $html->save();
            }
            echo $contents;*/
        } else {
            echo $contents;
        }
    }

    /**
     * @return array|mixed
     */
    protected function getData()
    {
        $locale = $this->requestHelper->getMasterRequest()->getLocale();
        if (!$locale) {
            return [];
        }

        $siteId = "";
        try {
            $site = Site::getCurrentSite();
            if ($site instanceof Site) {
                $siteId = $site->getId();
            }
        } catch (\Exception $e) {
            // not inside a site
        }

        $cacheKey = "glossary_" . $locale . "_" . $siteId;

        try {
            $data = \Pimcore\Cache\Runtime::get($cacheKey);

            return $data;
        } catch (\Exception $e) {
        }


        if (!$data = CacheManger::load($cacheKey)) {
            $list = new \Pimcore\Model\Glossary\Listing();
            $list->setCondition("(language = ? OR language IS NULL OR language = '') AND (site = ? OR site IS NULL OR site = '')", [$locale, $siteId]);
            $list->setOrderKey("LENGTH(`text`)", false);
            $list->setOrder("DESC");
            $data = $list->getDataArray();

            $data = $this->prepareData($data);

            CacheManger::save($data, $cacheKey, ["glossary"], null, 995);
            \Pimcore\Cache\Runtime::set($cacheKey, $data);
        }

        return $data;
    }

    /**
     * @param $data
     * @return array
     */
    protected function prepareData($data)
    {
        $mappedData = [];

        // fix htmlentities issues
        $tmpData = [];
        foreach ($data as $d) {
            if ($d["text"] != htmlentities($d["text"], null, "UTF-8")) {
                $td = $d;
                $td["text"] = htmlentities($d["text"], null, "UTF-8");
                $tmpData[] = $td;
            }
            $tmpData[] = $d;
        }

        $data = $tmpData;

        // prepare data
        foreach ($data as $d) {
            if ($d["link"] || $d["abbr"] || $d["acronym"]) {
                $r = $d["text"];
                if ($d["abbr"]) {
                    $r = '<abbr class="pimcore_glossary" title="' . $d["abbr"] . '">' . $r . '</abbr>';
                } elseif ($d["acronym"]) {
                    $r = '<acronym class="pimcore_glossary" title="' . $d["acronym"] . '">' . $r . '</acronym>';
                }

                $linkType = "";
                $linkTarget = "";

                if ($d["link"]) {
                    $linkType = "external";
                    $linkTarget = $d["link"];

                    if (intval($d["link"])) {
                        if ($doc = Document::getById($d["link"])) {
                            $d["link"] = $doc->getFullPath();
                            $linkType = "internal";
                            $linkTarget = $doc->getId();
                        }
                    }

                    $r = '<a class="pimcore_glossary" href="' . $d["link"] . '">' . $r . '</a>';
                }

                // add PCRE delimiter and modifiers
                if ($d["exactmatch"]) {
                    $d["text"] = "/(?<!\w)" . preg_quote($d["text"], "/") . "(?!\w)/";
                } else {
                    $d["text"] = "/" . preg_quote($d["text"], "/") . "/";
                }

                if (!$d["casesensitive"]) {
                    $d["text"] .= "i";
                }

                $mappedData[] = [
                    "replace" => $r,
                    "search" => $d["text"],
                    "linkType" => $linkType,
                    "linkTarget" => $linkTarget
                ];
            }
        }

        return $mappedData;
    }
}
